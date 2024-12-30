document.getElementById('generateAltBtn').addEventListener('click', function () {
    const imageUrl = this.getAttribute('img-src-data');

    if (!imageUrl) {
        alert('Please provide an image URL.');
        return;
    }

    document.getElementById('content-loading').textContent = 'Working on image alt...';
    document.getElementById('content-loading').classList.remove('hidden');

    // Send a POST request using Fetch
    fetch('generate-alt', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({ imageUrl: imageUrl })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('content-loading').textContent = '';
            document.getElementById('content-loading').classList.add('hidden');
            document.getElementById('altText').textContent = data;
            document.getElementById('altText').classList.add('text-green-600');
            if (data !== 'none') {
                document.getElementById('og_image_alt').value = data;
            }
        })
        .catch(error => {
            document.getElementById('content-loading').textContent = '';
            document.getElementById('content-loading').classList.add('hidden');
            document.getElementById('altText').textContent = 'Error generating alt text.';
            console.error('There was a problem with the fetch operation:', error);
        });
});