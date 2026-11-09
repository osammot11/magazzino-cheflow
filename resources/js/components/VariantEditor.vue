<script setup>
import { ref } from 'vue';
const props = defineProps({ initialVariants: { type: Array, default: () => [] }, isEditing: Boolean });
let nextKey = 0;
const blank = () => ({ name: '', sku: '', barcode: '', price: '0.00', low_stock_threshold: 5, initial_quantity: 0, _key: nextKey++ });
const variants = ref(props.initialVariants.length ? props.initialVariants.map(v => ({ ...v, _key: nextKey++ })) : [{ ...blank(), name: 'Standard' }]);
</script>
<template>
    <div class="variant-editor">
        <div v-for="(variant, index) in variants" :key="variant._key" class="variant-card">
            <div class="variant-heading"><strong>Variante {{ index + 1 }}</strong><span v-if="variant.id" class="badge neutral">{{ variant.quantity ?? '—' }} unità · quantità tramite movimenti</span><button v-else-if="variants.length > 1" type="button" class="remove-button" @click="variants.splice(index, 1)">Rimuovi</button></div>
            <input v-if="variant.id" type="hidden" :name="`variants[${index}][id]`" :value="variant.id">
            <div class="fields-grid">
                <label>Nome variante <span>*</span><input v-model="variant.name" :name="`variants[${index}][name]`" required maxlength="255" placeholder="Es. Nero / M"></label>
                <label>SKU <span>*</span><input v-model="variant.sku" :name="`variants[${index}][sku]`" required maxlength="100" placeholder="Es. CF-TSH-NER-M" class="sku-input"></label>
                <label>Prezzo di vendita (€) <span>*</span><input v-model="variant.price" :name="`variants[${index}][price]`" type="number" step="0.01" min="0" max="9999999" required></label>
                <label>Codice a barre<input v-model="variant.barcode" :name="`variants[${index}][barcode]`" maxlength="100" placeholder="Facoltativo"></label>
                <label>Soglia scorta minima <span>*</span><input v-model="variant.low_stock_threshold" :name="`variants[${index}][low_stock_threshold]`" type="number" min="0" max="100000000" required></label>
                <label v-if="!variant.id">Giacenza iniziale<input v-model="variant.initial_quantity" :name="`variants[${index}][initial_quantity]`" type="number" min="0" max="100000000" required></label>
            </div>
        </div>
        <button type="button" class="button" :disabled="variants.length >= 100" @click="variants.push(blank())">＋ Aggiungi variante</button>
        <p class="field-help">La giacenza iniziale genera un movimento di carico. Le varianti esistenti restano nello storico.</p>
    </div>
</template>
