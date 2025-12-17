export default () => ({
    sidebarOpen: false,
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    profileDropdown: false,
    dashboardDropdown: true,
    employeesDropdown: false,
    workspaceDropdown: false,
    
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
    },
    closeSidebar() {
        this.sidebarOpen = false;
    },
    toggleSidebarCollapse() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
    }
});
