<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { inventoryService } from '@/services/api/inventory.service'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()
const tab = ref('products')
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const categories = ref([])
const movements = ref([])
const search = ref('')
const categoryId = ref('')
const lowStockOnly = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const showItemModal = ref(false)
const showAdjustModal = ref(false)
const editing = ref(null)
const adjusting = ref(null)

const itemForm = reactive({
  sku: '',
  barcode: '',
  name: '',
  description: '',
  category_id: '',
  quantity: 0,
  reorder_level: 0,
  unit_cost: 0,
  unit_price: 0,
  tax_rate: 0,
  is_active: true,
})

const adjustForm = reactive({
  quantity_delta: 1,
  reason: '',
  type: 'adjustment',
})

const categoryName = ref('')

const filters = computed(() => ({
  search: search.value || undefined,
  category_id: categoryId.value || undefined,
  low_stock: lowStockOnly.value ? 1 : undefined,
  page: meta.value.current_page,
}))

async function loadProducts() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await inventoryService.list(filters.value)
    items.value = res.data
    meta.value = res.meta
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? 'Failed to load inventory.'
  } finally {
    isLoading.value = false
  }
}

async function loadCategories() {
  categories.value = await inventoryService.categories()
}

async function loadMovements() {
  const res = await inventoryService.adjustments()
  movements.value = res.data
}

function openCreate() {
  editing.value = null
  Object.assign(itemForm, {
    sku: '',
    barcode: '',
    name: '',
    description: '',
    category_id: '',
    quantity: 0,
    reorder_level: 0,
    unit_cost: 0,
    unit_price: 0,
    tax_rate: 0,
    is_active: true,
  })
  showItemModal.value = true
}

function openEdit(item) {
  editing.value = item
  Object.assign(itemForm, {
    sku: item.sku,
    barcode: item.barcode ?? '',
    name: item.name,
    description: item.description ?? '',
    category_id: item.category_id ?? '',
    quantity: item.quantity,
    reorder_level: item.reorder_level,
    unit_cost: item.unit_cost,
    unit_price: item.unit_price,
    tax_rate: item.tax_rate,
    is_active: item.is_active,
  })
  showItemModal.value = true
}

async function saveItem() {
  try {
    const payload = { ...itemForm, category_id: itemForm.category_id || null }
    if (editing.value) {
      delete payload.quantity
      await inventoryService.update(editing.value.id, payload)
    } else {
      await inventoryService.create(payload)
    }
    showItemModal.value = false
    await loadProducts()
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? 'Could not save item.'
  }
}

function openAdjust(item) {
  adjusting.value = item
  adjustForm.quantity_delta = 1
  adjustForm.reason = ''
  adjustForm.type = 'adjustment'
  showAdjustModal.value = true
}

async function saveAdjust() {
  try {
    await inventoryService.adjust(adjusting.value.id, { ...adjustForm })
    showAdjustModal.value = false
    await loadProducts()
    if (tab.value === 'movements') await loadMovements()
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? 'Could not adjust stock.'
  }
}

async function removeItem(item) {
  if (!confirm(`Delete ${item.name}?`)) return
  await inventoryService.remove(item.id)
  await loadProducts()
}

async function addCategory() {
  if (!categoryName.value.trim()) return
  await inventoryService.createCategory({ name: categoryName.value })
  categoryName.value = ''
  await loadCategories()
}

async function removeCategory(category) {
  if (!confirm(`Delete category ${category.name}?`)) return
  await inventoryService.removeCategory(category.id)
  await loadCategories()
}

async function downloadExport() {
  const blob = await inventoryService.exportCsv()
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'inventory.csv'
  link.click()
  URL.revokeObjectURL(url)
}

onMounted(async () => {
  await Promise.all([loadProducts(), loadCategories()])
})
</script>

<template>
  <div>
    <header class="erp-header">
      <h1>Inventory</h1>
      <div>
        <button v-can="'inventory.export'" class="btn" type="button" @click="downloadExport">Export CSV</button>
        <button v-can="'inventory.create'" class="btn btn--primary" type="button" @click="openCreate">+ Product</button>
      </div>
    </header>

    <div class="erp-toolbar">
      <button class="btn" :class="{ 'btn--primary': tab === 'products' }" type="button" @click="tab = 'products'">
        Products
      </button>
      <button class="btn" :class="{ 'btn--primary': tab === 'movements' }" type="button" @click="tab = 'movements'; loadMovements()">
        Movements
      </button>
      <button class="btn" :class="{ 'btn--primary': tab === 'categories' }" type="button" @click="tab = 'categories'">
        Categories
      </button>
    </div>

    <p v-if="errorMessage" class="alert alert--error">{{ errorMessage }}</p>

    <div v-if="tab === 'products'">
      <div class="erp-toolbar">
        <input v-model="search" placeholder="Search name, SKU, barcode" @keyup.enter="loadProducts" />
        <select v-model="categoryId" @change="loadProducts">
          <option value="">All categories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </select>
        <label>
          <input v-model="lowStockOnly" type="checkbox" @change="loadProducts" />
          Low stock
        </label>
        <button class="btn" type="button" @click="loadProducts">Search</button>
      </div>

      <p v-if="isLoading">Loading...</p>
      <table v-else class="erp-table">
        <thead>
          <tr>
            <th>SKU</th>
            <th>Name</th>
            <th>Category</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Status</th>
            <th v-if="can('inventory.update') || can('inventory.delete') || can('inventory.adjust_stock')">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.sku }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.category?.name ?? '—' }}</td>
            <td>
              {{ item.quantity }}
              <span v-if="item.is_low_stock" class="badge badge--warn">Low</span>
            </td>
            <td>{{ Number(item.unit_price).toFixed(2) }}</td>
            <td>
              <span class="badge" :class="item.is_active ? 'badge--ok' : ''">{{ item.is_active ? 'Active' : 'Inactive' }}</span>
            </td>
            <td>
              <button v-can="'inventory.update'" class="btn btn--sm" type="button" @click="openEdit(item)">Edit</button>
              <button v-can="'inventory.adjust_stock'" class="btn btn--sm" type="button" @click="openAdjust(item)">Adjust</button>
              <button v-can="'inventory.delete'" class="btn btn--sm btn--danger" type="button" @click="removeItem(item)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p>Total {{ meta.total }}</p>
    </div>

    <div v-else-if="tab === 'movements'">
      <table class="erp-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Item</th>
            <th>Type</th>
            <th>Delta</th>
            <th>Reason</th>
            <th>Ref</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in movements" :key="row.id">
            <td>{{ row.created_at }}</td>
            <td>{{ row.item?.sku }} {{ row.item?.name }}</td>
            <td>{{ row.type }}</td>
            <td>{{ row.quantity_delta }}</td>
            <td>{{ row.reason }}</td>
            <td>{{ row.reference }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else>
      <div class="erp-toolbar">
        <input v-model="categoryName" placeholder="New category name" />
        <button v-can="'inventory.create'" class="btn btn--primary" type="button" @click="addCategory">Add</button>
      </div>
      <table class="erp-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Items</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in categories" :key="category.id">
            <td>{{ category.name }}</td>
            <td>{{ category.items_count }}</td>
            <td>
              <button v-can="'inventory.delete'" class="btn btn--sm btn--danger" type="button" @click="removeCategory(category)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showItemModal" class="erp-modal" @click.self="showItemModal = false">
      <div class="erp-modal__card">
        <h2>{{ editing ? 'Edit product' : 'New product' }}</h2>
        <form class="erp-form" @submit.prevent="saveItem">
          <label>SKU<input v-model="itemForm.sku" required /></label>
          <label>Barcode<input v-model="itemForm.barcode" /></label>
          <label>Name<input v-model="itemForm.name" required /></label>
          <label>Category
            <select v-model="itemForm.category_id">
              <option value="">None</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </label>
          <label v-if="!editing">Opening qty<input v-model.number="itemForm.quantity" type="number" min="0" /></label>
          <label>Reorder level<input v-model.number="itemForm.reorder_level" type="number" min="0" /></label>
          <label>Cost<input v-model.number="itemForm.unit_cost" type="number" min="0" step="0.01" /></label>
          <label>Price<input v-model.number="itemForm.unit_price" type="number" min="0" step="0.01" /></label>
          <label>Tax %<input v-model.number="itemForm.tax_rate" type="number" min="0" step="0.01" /></label>
          <label><input v-model="itemForm.is_active" type="checkbox" /> Active</label>
          <div>
            <button class="btn btn--primary" type="submit">Save</button>
            <button class="btn" type="button" @click="showItemModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showAdjustModal" class="erp-modal" @click.self="showAdjustModal = false">
      <div class="erp-modal__card">
        <h2>Adjust {{ adjusting?.name }}</h2>
        <form class="erp-form" @submit.prevent="saveAdjust">
          <label>Qty change (+/-)<input v-model.number="adjustForm.quantity_delta" type="number" required /></label>
          <label>Type
            <select v-model="adjustForm.type">
              <option value="adjustment">Adjustment</option>
              <option value="count">Count</option>
              <option value="damage">Damage</option>
              <option value="return">Return</option>
            </select>
          </label>
          <label>Reason<input v-model="adjustForm.reason" /></label>
          <div>
            <button class="btn btn--primary" type="submit">Apply</button>
            <button class="btn" type="button" @click="showAdjustModal = false">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
