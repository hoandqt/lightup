document.getElementById('create-metadata').addEventListener('click', function() {
    const title = document.getElementById('title').value;
    const description = document.getElementById('description').value;
    const tags = document.getElementById('tags').value;
    
    if (!title || !description || !tags) {
        alert('Please fill out the Title, Description, and Tags fields before generating metadata.');
        return;
    }
    
    document.getElementById('metadata-loading').classList.remove('hidden');

    fetch('/generate-metadata', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ title, description, tags }),
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('metadata-loading').classList.add('hidden');
        if (data.success) {
            document.getElementById('meta_title').value = data.meta_title;
            document.getElementById('meta_description').value = data.meta_description;
            document.getElementById('meta_keywords').value = data.meta_keywords;
        } else {
            alert('Failed to generate metadata: ' + data.message);
        }
    })
    .catch(error => {
        document.getElementById('metadata-loading').classList.add('hidden');
        console.error('Error generating metadata:', error);
        alert('An error occurred while generating metadata.');
    });
});
