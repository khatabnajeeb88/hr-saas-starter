export default () => ({
    sidebarOpen: false,
    profileDropdown: false,
    dashboardDropdown: true,
    workspaceDropdown: false,
    
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
    },
    closeSidebar() {
        this.sidebarOpen = false;
    }
});
