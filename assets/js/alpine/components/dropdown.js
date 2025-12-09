export default (initialState = false) => ({
    open: initialState,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    }
});
