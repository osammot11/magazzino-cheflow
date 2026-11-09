import { createApp } from 'vue';
import VariantEditor from './components/VariantEditor.vue';
import StockMovement from './components/StockMovement.vue';
const components = { variants: VariantEditor, movement: StockMovement };
document.querySelectorAll('[data-vue]').forEach((element) => {
    createApp(components[element.dataset.vue], JSON.parse(element.dataset.props)).mount(element);
});
document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
        if (form.method.toLowerCase() === 'post') {
            form.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = true; });
        }
    });
});
