<script setup>
import { computed, ref } from 'vue';
const props = defineProps({ variants: { type: Array, required: true }, initial: { type: Object, required: true } });
const selectedId = ref(String(props.initial.variant_id ?? ''));
const type = ref(props.initial.type === 'out' ? 'out' : 'in');
const quantity = ref(props.initial.quantity);
const search = ref('');
const selected = computed(() => props.variants.find(v => String(v.id) === selectedId.value));
const options = computed(() => props.variants.filter(v => v.label.toLowerCase().includes(search.value.toLowerCase()) || String(v.id) === selectedId.value));
const after = computed(() => (selected.value?.quantity ?? 0) + (type.value === 'in' ? 1 : -1) * Number(quantity.value || 0));
const invalid = computed(() => !selected.value || !Number.isInteger(Number(quantity.value)) || Number(quantity.value) < 1 || after.value < 0);
</script>
<template>
    <div class="form-layout movement-layout"><section class="card form-card">
        <h2>Dettagli del movimento</h2>
        <fieldset class="type-options"><legend>Tipo di movimento</legend><label :class="{ chosen: type === 'in' }"><input v-model="type" name="type" type="radio" value="in"><span>↙</span><div><strong>Carico</strong><small>Aggiungi unità al magazzino</small></div></label><label :class="{ chosen: type === 'out' }"><input v-model="type" name="type" type="radio" value="out"><span>↗</span><div><strong>Scarico</strong><small>Rimuovi unità dal magazzino</small></div></label></fieldset>
        <label>Cerca prodotto o SKU<input v-model="search" type="search" placeholder="Filtra le varianti disponibili…"></label>
        <label>Prodotto / Variante <span>*</span><select v-model="selectedId" name="variant_id" required><option disabled value="">Seleziona una variante</option><option v-for="variant in options" :key="variant.id" :value="String(variant.id)">{{ variant.label }} ({{ variant.quantity }} disp.)</option></select></label>
        <div class="fields-grid"><label>Quantità <span>*</span><input v-model="quantity" name="quantity" type="number" min="1" :max="type === 'out' ? selected?.quantity : 100000000" required></label><label>Causale <span>*</span><input name="reason" :value="initial.reason" list="reasons" maxlength="255" placeholder="Seleziona o scrivi una causale" required><datalist id="reasons"><option>Acquisto da fornitore</option><option>Vendita</option><option>Reso cliente</option><option>Reso a fornitore</option><option>Inventario / Rettifica</option><option>Danneggiamento</option><option>Uso interno</option></datalist></label></div>
        <label>Riferimento<input name="reference" :value="initial.reference" maxlength="255" placeholder="Es. Ordine #1024, DDT o fattura"></label>
        <label>Note<textarea name="notes" :value="initial.notes" rows="3" maxlength="2000" placeholder="Aggiungi informazioni utili (facoltativo)"></textarea></label>
    </section><aside><section class="card form-card summary-card"><div class="eyebrow">ANTEPRIMA</div><h2>Disponibilità aggiornata</h2><p>{{ selected ? selected.label : 'Seleziona una variante per vedere il riepilogo.' }}</p><div class="summary-line"><span>Giacenza attuale</span><strong>{{ selected?.quantity ?? '—' }}</strong></div><div class="summary-line"><span>{{ type === 'in' ? 'Carico' : 'Scarico' }}</span><strong>{{ type === 'in' ? '+' : '−' }}{{ quantity || 0 }}</strong></div><div class="summary-total"><span>Nuova disponibilità</span><strong :class="{ 'negative-text': after < 0 }">{{ selected ? after : '—' }}</strong></div><p v-if="selected && after < 0" class="negative-text" role="alert">Disponibilità insufficiente per questo scarico.</p><small>La disponibilità effettiva viene verificata al salvataggio.</small></section><div class="info-box"><strong>Ogni movimento lascia una traccia</strong><p>Il movimento verrà conservato nello storico. Per correggerlo, registra un movimento di segno opposto.</p></div></aside></div>
    <div class="form-actions"><button class="button primary" type="submit" :disabled="invalid">Registra {{ type === 'in' ? 'carico' : 'scarico' }}</button></div>
</template>
