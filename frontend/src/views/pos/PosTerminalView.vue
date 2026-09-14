<script setup>
import { computed, onMounted, ref } from 'vue'
import { inventoryService } from '@/services/api/inventory.service'
import { customerService, posService } from '@/services/api/pos.service'
const products = ref([])
const categories = ref([])
const customers = ref([])
const search = ref('')
const categoryId = ref('')
const barcode = ref('')
const cart = ref([])
const customerId = ref('')
const discountTotal = ref(0)
const payMethod = ref('cash')
const tendered = ref(0)
const notes = ref('')
const errorMessage = ref('')
const receipt = ref(null)
const summary = ref(null)

const subtotal = computed(() =>
  cart.value.reduce((sum, line) => sum + line.quantity * Number(line.unit_price) - Number(line.discount || 0), 0)
)
const taxTotal = computed(() =>
  cart.value.reduce((sum, line) => {
    const net = line.quantity * Number(line.unit_price) - Number(line.discount || 0)
    return sum + net * (Number(line.tax_rate || 0) / 100)
  }, 0)
)
const total = computed(() => Math.max(0, subtotal.value + taxTotal.value - Number(discountTotal.value || 0)))
const changeDue = computed(() => Math.max(0, Number(tendered.value || 0) - total.value))

async function loadCatalog() {
  products.value = await posService.catalog({
    search: search.value || undefined,
    category_id: categoryId.value || undefined,
  })
}

async function scanBarcode() {
  if (!barcode.value.trim()) return
  try {
    const item = await inventoryService.lookup(barcode.value.trim())
    addToCart(item)
    barcode.value = ''
    errorMessage.value = ''
  } catch {
    errorMessage.value = 'Barcode not found.'
  }
}

function addToCart(item) {
  if (item.quantity < 1) {
    errorMessage.value = `${item.name} is out of stock.`
    return
  }
  const existing = cart.value.find((line) => line.inventory_item_id === item.id)
  if (existing) {
    if (existing.quantity + 1 > item.quantity) {
      errorMessage.value = `Only ${item.quantity} available.`
      return
    }
    existing.quantity += 1
    return
  }
  cart.value.push({
    inventory_item_id: item.id,
    name: item.name,
    sku: item.sku,
    quantity: 1,
    available: item.quantity,
    unit_price: Number(item.unit_price),
    tax_rate: Number(item.tax_rate || 0),
    discount: 0,
  })
}

function changeQty(line, delta) {
  line.quantity = Math.max(1, line.quantity + delta)
  if (line.quantity > line.available) line.quantity = line.available
}

function removeLine(index) {
  cart.value.splice(index, 1)
}

async function checkout() {
  errorMessage.value = ''
  try {
    const amount = payMethod.value === 'cash' ? Math.max(total.value, Number(tendered.value || 0)) : total.value
    const res = await posService.checkout({
      customer_id: customerId.value || null,
      discount_total: Number(discountTotal.value || 0),
      notes: notes.value || null,
      items: cart.value.map((line) => ({
        inventory_item_id: line.inventory_item_id,
        quantity: line.quantity,
        unit_price: line.unit_price,
        discount: line.discount,
        tax_rate: line.tax_rate,
      })),
      payments: [{ method: payMethod.value, amount }],
    })
    receipt.value = res.data
    cart.value = []
    discountTotal.value = 0
    tendered.value = 0
    notes.value = ''
    await Promise.all([loadCatalog(), loadSummary()])
  } catch (error) {
    errorMessage.value = error?.response?.data?.message
      ?? Object.values(error?.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Checkout failed.'
  }
}

async function loadSummary() {
  summary.value = await posService.summary()
}

onMounted(async () => {
  await Promise.all([loadCatalog(), loadSummary()])
  categories.value = await inventoryService.categories()
  try {
    customers.value = await customerService.list()
  } catch {
    customers.value = []
  }
})
</script>

<template>
  <div>
    <header class="erp-header">
      <h1>Point of Sale</h1>
      <p v-if="summary">Today: {{ summary.count }} sales / {{ Number(summary.total).toFixed(2) }}</p>
    </header>

    <p v-if="errorMessage" class="alert alert--error">{{ errorMessage }}</p>

    <div class="pos-layout">
      <section>
        <div class="erp-toolbar">
          <input v-model="barcode" placeholder="Scan or type barcode/SKU" @keyup.enter="scanBarcode" />
          <button class="btn" type="button" @click="scanBarcode">Add</button>
          <input v-model="search" placeholder="Search products" @keyup.enter="loadCatalog" />
          <select v-model="categoryId" @change="loadCatalog">
            <option value="">All</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
          </select>
        </div>
        <div class="pos-grid">
          <button v-for="item in products" :key="item.id" class="pos-card" type="button" @click="addToCart(item)">
            <h3>{{ item.name }}</h3>
            <p>{{ item.sku }} · {{ Number(item.unit_price).toFixed(2) }}</p>
            <p>
              Stock {{ item.quantity }}
              <span v-if="item.is_low_stock" class="badge badge--warn">Low</span>
            </p>
          </button>
        </div>
      </section>

      <aside class="pos-cart">
        <h2>Cart</h2>
        <div v-if="!cart.length">No items.</div>
        <div v-for="(line, index) in cart" :key="line.inventory_item_id" class="pos-cart__line">
          <div>
            <strong>{{ line.name }}</strong>
            <div>{{ Number(line.unit_price).toFixed(2) }} × {{ line.quantity }}</div>
            <button class="btn btn--sm" type="button" @click="changeQty(line, -1)">-</button>
            <button class="btn btn--sm" type="button" @click="changeQty(line, 1)">+</button>
            <button class="btn btn--sm btn--danger" type="button" @click="removeLine(index)">x</button>
          </div>
          <div>{{ (line.quantity * line.unit_price).toFixed(2) }}</div>
        </div>

        <label>Customer
          <select v-model="customerId">
            <option value="">Walk-in</option>
            <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option>
          </select>
        </label>
        <label>Discount<input v-model.number="discountTotal" type="number" min="0" step="0.01" /></label>
        <label>Pay with
          <select v-model="payMethod">
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="mobile">Mobile</option>
          </select>
        </label>
        <label v-if="payMethod === 'cash'">Tendered<input v-model.number="tendered" type="number" min="0" step="0.01" /></label>
        <p>Subtotal {{ subtotal.toFixed(2) }}</p>
        <p>Tax {{ taxTotal.toFixed(2) }}</p>
        <p><strong>Total {{ total.toFixed(2) }}</strong></p>
        <p v-if="payMethod === 'cash'">Change {{ changeDue.toFixed(2) }}</p>
        <button
          v-can="'pos.sell'"
          class="btn btn--primary"
          type="button"
          :disabled="!cart.length"
          @click="checkout"
        >
          Complete sale
        </button>
      </aside>
    </div>

    <div v-if="receipt" class="erp-modal" @click.self="receipt = null">
      <div class="erp-modal__card">
        <h2>Receipt {{ receipt.number }}</h2>
        <p>{{ receipt.sold_at }}</p>
        <table class="erp-table">
          <tr v-for="line in receipt.items" :key="line.id">
            <td>{{ line.name }} × {{ line.quantity }}</td>
            <td>{{ Number(line.line_total).toFixed(2) }}</td>
          </tr>
        </table>
        <p><strong>Total {{ Number(receipt.total).toFixed(2) }}</strong></p>
        <p>Paid {{ Number(receipt.amount_paid).toFixed(2) }} · Change {{ Number(receipt.change_due).toFixed(2) }}</p>
        <button class="btn" type="button" @click="receipt = null">Close</button>
      </div>
    </div>
  </div>
</template>
