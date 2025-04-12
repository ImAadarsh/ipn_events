        <!-- Page Content Ends Here -->
    </div><!-- .main-content -->
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Sidebar toggle
        document.getElementById('sidebar-toggle')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.overlay').classList.toggle('active');
            
            // Add body class to prevent scrolling when sidebar is open
            document.body.classList.toggle('sidebar-open');
        });

        // Close sidebar when overlay is clicked
        document.getElementById('sidebar-overlay')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
            document.querySelector('.overlay').classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
        
        // Close sidebar with close button
        document.getElementById('sidebar-close')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
            document.querySelector('.overlay').classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });

        // Close sidebar when navigation link is clicked on mobile
        if (window.innerWidth < 992) {
            document.querySelectorAll('.sidebar-nav li a').forEach(link => {
                link.addEventListener('click', function() {
                    document.querySelector('.sidebar').classList.remove('active');
                    document.querySelector('.overlay').classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                });
            });
        }
        
        // Initialize DataTables for visible tables only
        $(document).ready(function() {
            // Handle navbar hide/show on scroll for mobile devices
            if (window.innerWidth < 992) {
                let lastScrollTop = 0;
                const navbar = document.querySelector('.navbar');
                const scrollThreshold = 10;
                
                window.addEventListener('scroll', function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    // Don't hide navbar when sidebar is open
                    if (document.body.classList.contains('sidebar-open')) {
                        return;
                    }
                    
                    // Determine scroll direction
                    if (Math.abs(scrollTop - lastScrollTop) > scrollThreshold) {
                        if (scrollTop > lastScrollTop && scrollTop > 60) {
                            // Scrolling down & past navbar height
                            navbar.classList.add('nav-up');
                            navbar.classList.remove('nav-down');
                        } else {
                            // Scrolling up
                            navbar.classList.add('nav-down');
                            navbar.classList.remove('nav-up');
                        }
                        lastScrollTop = scrollTop;
                    }
                });
            }
            
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    responsive: true,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3"lf>rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center"ip>',
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    }
                });
            }
            
            if ($('.datatable-export').length) {
                $('.datatable-export').DataTable({
                    responsive: true,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    dom: '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3"lBf>rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center"ip>',
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel me-2"></i>Excel',
                            className: 'btn btn-sm btn-success me-2',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'csv',
                            text: '<i class="fas fa-file-csv me-2"></i>CSV',
                            className: 'btn btn-sm btn-primary me-2',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print me-2"></i>Print',
                            className: 'btn btn-sm btn-secondary',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }
                    ],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    }
                });
            }
            
            // Wrap all tables with responsive containers if not already wrapped
            $('.table:not(.dataTable)').each(function() {
                if (!$(this).parent().hasClass('table-responsive')) {
                    $(this).wrap('<div class="table-responsive"></div>');
                }
            });
            
            // Ensure form inputs and buttons are big enough for touch on mobile
            if (window.innerWidth < 768) {
                $('input, select, textarea, .btn').addClass('form-control-lg');
                $('.btn-sm').removeClass('form-control-lg');
            }
        });
    </script>
</body>
</html> 