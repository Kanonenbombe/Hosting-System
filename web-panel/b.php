        function createVM() {
            const formData = {
                template_name: document.getElementById('template_name').value,
                ram: parseInt(document.getElementById('ram').value, 10),
                cpus: parseInt(document.getElementById('cpus').value, 10),
                storage: parseInt(document.getElementById('storage').value, 10),
                token: document.getElementById('token').value
            };

            fetch('http://127.0.0.1:8000/select-server-and-create-vm/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => alert(JSON.stringify(data)))
            .catch((error) => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten!');
            });
        }