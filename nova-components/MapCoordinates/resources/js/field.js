Nova.booting((Vue, router, store) => {
    Vue.component('index-MapCoordinates', require('./components/IndexField'))
    Vue.component('detail-MapCoordinates', require('./components/DetailField'))
    Vue.component('form-MapCoordinates', require('./components/FormField'))
})
