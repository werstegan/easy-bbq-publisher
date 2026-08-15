document.addEventListener('DOMContentLoaded', () => {
    const statusArea = document.getElementById('ebsp-presets-status');
    if (!statusArea) return; // Only run on presets page

    let currentPresets = {
        starters: [],
        mains: [],
        drinks: []
    };

    const fetchPresets = async () => {
        try {
            statusArea.innerText = 'Loading presets...';
            const response = await fetch(ebspSettings.restUrl + 'presets', {
                headers: {
                    'X-WP-Nonce': ebspSettings.nonce
                }
            });
            const data = await response.json();
            if (response.ok) {
                currentPresets = data;
                renderPresets();
                statusArea.innerText = '';
            } else {
                statusArea.innerText = 'Error loading presets.';
            }
        } catch (e) {
            statusArea.innerText = 'Error: ' + e.message;
        }
    };

    const resetPresets = async () => {
        try {
            statusArea.innerText = 'Resetting presets...';
            const response = await fetch(ebspSettings.restUrl + 'reset-presets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ebspSettings.nonce
                }
            });
            const data = await response.json();
            if (response.ok) {
                currentPresets = data.presets;
                renderPresets();
                statusArea.innerText = 'Presets successfully reset and merged.';
                setTimeout(() => statusArea.innerText = '', 3000);
            } else {
                statusArea.innerText = 'Error resetting presets.';
            }
        } catch (e) {
            statusArea.innerText = 'Error: ' + e.message;
        }
    };

    const savePresets = async () => {
        try {
            statusArea.innerText = 'Saving...';
            const response = await fetch(ebspSettings.restUrl + 'presets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ebspSettings.nonce
                },
                body: JSON.stringify(currentPresets)
            });
            if (response.ok) {
                statusArea.innerText = 'Presets saved successfully.';
                setTimeout(() => statusArea.innerText = '', 2000);
            } else {
                statusArea.innerText = 'Error saving presets.';
            }
        } catch (e) {
            statusArea.innerText = 'Error: ' + e.message;
        }
    };

    const renderPresets = () => {
        ['starters', 'mains', 'drinks'].forEach(type => {
            const listEl = document.querySelector(`.ebsp-preset-section[data-type="${type}"] .ebsp-preset-list`);
            listEl.innerHTML = '';

            currentPresets[type].forEach((item, index) => {
                const li = document.createElement('li');

                const input = document.createElement('input');
                input.type = 'text';
                input.value = item;
                input.className = 'ebsp-preset-input';
                input.addEventListener('change', (e) => {
                    currentPresets[type][index] = e.target.value.trim();
                    savePresets();
                });

                const btnDel = document.createElement('button');
                btnDel.type = 'button';
                btnDel.className = 'button btn-delete-preset';
                btnDel.innerHTML = '🗑';
                btnDel.title = 'Supprimer';
                btnDel.addEventListener('click', () => {
                    currentPresets[type].splice(index, 1);
                    renderPresets();
                    savePresets();
                });

                li.appendChild(input);
                li.appendChild(btnDel);
                listEl.appendChild(li);
            });
        });
    };

    const btnReset = document.getElementById('ebsp-btn-reset-presets');
    if (btnReset) {
        btnReset.addEventListener('click', () => {
            if(confirm("Are you sure? This will add back all default items. Custom items won't be deleted.")) {
                resetPresets();
            }
        });
    }

    document.querySelectorAll('.btn-add-preset').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const section = e.target.closest('.ebsp-preset-section');
            const type = section.dataset.type;
            const input = section.querySelector('.ebsp-preset-add input');
            const val = input.value.trim();

            if (val && !currentPresets[type].includes(val)) {
                currentPresets[type].push(val);
                input.value = '';
                renderPresets();
                savePresets();
            }
        });
    });

    fetchPresets();
});
