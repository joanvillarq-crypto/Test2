        function loadContent() {
            const loader = document.getElementById('loader');
            const content = document.getElementById('player');

            // Show loader immediately
            loader.style.display = 'block';
            content.style.display = 'none';

            // Simulate a delay for loading content
            setTimeout(() => {
                loader.style.display = 'none';   // Hide loader
                content.style.display = 'block';  // Show content
            }, 2000); // Set a shorter delay for button click (2000ms = 2 seconds)
        }

        // Call the loadContent function on window load
        window.onload = function() {
            // Show loader for a longer delay on page load
            const loader = document.getElementById('loader');
            const content = document.getElementById('player');

            // Simulate a delay for loading content
            setTimeout(() => {
                loader.style.display = 'none';   // Hide loader
                content.style.display = 'block';  // Show content
            }, 5000); // Longer delay for the initial load (5000ms = 5 seconds)
        };

        // Add event listener for button click
        document.getElementById('playButton').onclick = loadContent;