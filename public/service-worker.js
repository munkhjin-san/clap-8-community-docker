self.addEventListener('fetch', function(event) {
    event.respondWith(
      // Try to fetch the request from the network
      fetch(event.request)
        .then(function(response) {
          // If successful, cache the response for future use
          if (response.status === 200) {
            // Clone the response before caching it
            const responseClone = response.clone();
            caches.open('my-cache').then(function(cache) {
              cache.put(event.request, responseClone);
            });
          }
          // Return the original response to the page
          return response;
        })
        .catch(function() {
          // If the request fails, try to serve it from the cache
          return caches.match(event.request);
        })
    );
  });
  