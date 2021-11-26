export default () => {
    document.getElementById('btnToggleProfileDropdown')?.addEventListener('click', function (event) {
        event.preventDefault()
        document.getElementById('profileDropdown')?.classList.toggle('hidden')
        document.getElementById('profileDropdown')?.classList.toggle('block')
    });

    document.getElementById('btnToggleSidebar')?.addEventListener('click', function (event) {
        event.preventDefault()
        document.getElementById('sidebar')?.classList.toggle('hidden')
        document.getElementById('sidebar')?.classList.toggle('block')
        document.getElementById('sidebarOverlay')?.classList.toggle('hidden')
        document.getElementById('sidebarOverlay')?.classList.toggle('block')
    });

    document.getElementById('btnCloseSidebar')?.addEventListener('click', function (event) {
        event.preventDefault()
        document.getElementById('sidebar')?.classList.add('hidden')
        document.getElementById('sidebarOverlay')?.classList.add('hidden')
    });

}
