(function() {
    function getTrackingCode() {
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].src;
            var match = src.match(/code=([a-zA-Z0-9\-]+)/);
            if (match) return match[1];
        }
        return null;
    }
    var code = getTrackingCode();

    function sendEvent(event, details) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'http://tuwa.co.ua//wp-json/solvera/v1/track', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            code: code,
            event: event,
            url: window.location.href,
            referrer: document.referrer,
            timestamp: Date.now(),
            details: details || {}
        }));
    }

    if (code) {
        sendEvent('pageview', {});

        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function() {
                sendEvent('lead', {form_action: form.action});
            });
        });

        document.querySelectorAll('.add-to-cart').forEach(function(btn) {
            btn.addEventListener('click', function() {
                sendEvent('add_to_cart', {product: btn.dataset.productId || null});
            });
        });

        document.querySelectorAll('.checkout').forEach(function(btn) {
            btn.addEventListener('click', function() {
                sendEvent('purchase', {order_id: btn.dataset.orderId || null});
            });
        });
    }
})(); 