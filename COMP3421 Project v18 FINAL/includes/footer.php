    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Wait for the window load event to ensure all resources are loaded
        window.addEventListener('load', function() {
            // Calculate the page load time (time from navigation start to load event)
            const loadTime = performance.now() / 1000; // Convert to seconds
            const currentPage = window.location.pathname;
            
            // Log performance data once per page load (avoid duplicate entries on SPA navigation)
            if (sessionStorage.getItem('performanceLogged_' + currentPage) !== 'true') {
                // Use the current base URL path
                const baseUrl = '<?php echo $base_url; ?>';
                
                // Log the performance data to our API
                fetch(baseUrl + '/api/log_performance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        pageUrl: currentPage,
                        loadTime: loadTime
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Performance data logged:', data);
                    // Mark this page as having logged performance data for this session
                    sessionStorage.setItem('performanceLogged_' + currentPage, 'true');
                })
                .catch(error => {
                    console.error('Error logging performance data:', error);
                });
            }
        });
    </script>
</body>
</html> 