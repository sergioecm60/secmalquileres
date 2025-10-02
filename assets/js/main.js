// assets/js/main.js

// Función para mostrar tabs en formularios (ej: Contratos)
function showTab(tabName, event) {
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => tab.classList.remove('active'));
    contents.forEach(content => content.classList.remove('active'));
    
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}