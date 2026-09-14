<script setup>
import { onMounted, ref } from 'vue'
import { posService } from '@/services/api/pos.service'

const sales = ref([])
const search = ref('')
const status = ref('')
const errorMessage = ref('')
const selected = ref(null)

async function loadSales() {
  try {
    const res = await posService.sales({
      search: search.value || undefined,
      status: status.value || undefined,
    })
    sales.value = res.data
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? 'Failed to load sales.'
  }
}

async function voidSale(sale) {
  if (!confirm(`Void ${sale.number}? Stock will be restored.`)) return
  try {
    await posService.voidSale(sale.id)
    await loadSales()
    selected.value = null
  } catch (error) {
    errorMessage.value = error?.response?.data?.message ?? 'Could not void sale.'
  }
}

async function openSale(sale) {
  selected.value = await posService.show(sale.id)
}

onMounted(loadSales)
</script>

<template>
  <div>
    <header class="erp-header">
      <h1>POS receipts</h1>
    </header>
    <div class="erp-toolbar">
      <input v-model="search" placeholder="Search receipt #" @keyup.enter="loadSales" />
      <select v-model="status" @change="loadSales">
        <option value="">All</option>
        <option value="completed">Completed</option>
        <option value="voided">Voided</option>
      </select>
      <button class="btn" type="button" @click="loadSales">Search</button>
    </div>
    <p v-if="errorMessage" class="alert alert--error">{{ errorMessage }}</p>
    <table class="erp-table">
      <thead>
        <tr>
          <th>Number</th>
          <th>Status</th>
          <th>Total</th>
          <th>Cashier</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="sale in sales" :key="sale.id">
          <td>{{ sale.number }}</td>
          <td>{{ sale.status }}</td>
          <td>{{ Number(sale.total).toFixed(2) }}</td>
          <td>{{ sale.cashier?.name ?? '—' }}</td>
          <td>
            <button class="btn btn--sm" type="button" @click="openSale(sale)">View</button>
            <button
              v-if="sale.status !== 'voided'"
              v-can="'pos.void'"
              class="btn btn--sm btn--danger"
              type="button"
              @click="voidSale(sale)"
            >
              Void
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="selected" class="erp-modal" @click.self="selected = null">
      <div class="erp-modal__card">
        <h2>{{ selected.number }}</h2>
        <p>{{ selected.status }} · {{ selected.sold_at }}</p>
        <table class="erp-table">
          <tr v-for="line in selected.items" :key="line.id">
            <td>{{ line.name }} × {{ line.quantity }}</td>
            <td>{{ Number(line.line_total).toFixed(2) }}</td>
          </tr>
        </table>
        <p>Total {{ Number(selected.total).toFixed(2) }}</p>
        <button class="btn" type="button" @click="selected = null">Close</button>
      </div>
    </div>
  </div>
</template>
