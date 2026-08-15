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

    // Batch Generation Logic
    const batchDishes = [
        { slug: 'crema-calabacin', name: 'Crema de calabacín' },
        { slug: 'sancocho', name: 'Sancocho ecuatoriano' },
        { slug: 'sopa-menestron', name: 'Sopa de menestrón de carne' },
        { slug: 'caldo-gallina', name: 'Caldo de gallina' },
        { slug: 'bolon-verde', name: 'Bolón de verde' },
        { slug: 'empanadas', name: 'Empanadas colombianas' },
        { slug: 'arepas', name: 'Arepas rellenas' },
        { slug: 'maduro-queso', name: 'Maduro con queso' },
        { slug: 'tallarines-salteados', name: 'Tallarines salteados de ternera con verduras' },
        { slug: 'chaulafan', name: 'Chaulafán ecuatoriano' },
        { slug: 'sango-camaron', name: 'Sango de camarón' },
        { slug: 'pescado-apanado', name: 'Pescado apanado con arroz' },
        { slug: 'pollo-apanado', name: 'Pollo apanado con arroz' },
        { slug: 'chuleta-cerdo', name: 'Chuleta de cerdo con arroz' },
        { slug: 'bandeja-paisa', name: 'Bandeja paisa' },
        { slug: 'encebollado', name: 'Encebollado de pescado' },
        { slug: 'guatita', name: 'Guatita tradicional' },
        { slug: 'ceviche', name: 'Ceviche' },
        { slug: 'bollo-pescado', name: 'Bollo de pescado' },
        { slug: 'fritada', name: 'Fritada ecuatoriana' },
        { slug: 'hornado', name: 'Hornado tradicional' },
        { slug: 'chicharron', name: 'Chicharrón' },
        { slug: 'churrasco', name: 'Churrasco ecuatoriano' },
        { slug: 'arroz-menestra', name: 'Arroz con menestra y carne asada' },
        { slug: 'arroz-pollo', name: 'Arroz con pollo' },
        { slug: 'arroz-camaron', name: 'Arroz con camarón' },
        { slug: 'gambas-ajillo', name: 'Gambas al ajillo' },
        { slug: 'bandera', name: 'Bandera ecuatoriana' },
        { slug: 'sango-pescado', name: 'Sango de pescado' },
        { slug: 'bebida-frutas', name: 'Bebida refrescante de frutas' }
    ];

    const btnBatchGenerate = document.getElementById('ebsp-btn-batch-generate');
    const batchContainer = document.getElementById('ebsp-batch-progress-container');
    const progressBar = document.getElementById('ebsp-progress-bar');
    const batchStatus = document.getElementById('ebsp-batch-status');

    const sleep = ms => new Promise(r => setTimeout(r, ms));

    if (btnBatchGenerate) {
        btnBatchGenerate.addEventListener('click', async () => {
            if (!confirm('Êtes-vous sûr de vouloir générer les 30 images par défaut (cela peut prendre quelques minutes et consommer votre quota API) ?')) return;

            btnBatchGenerate.disabled = true;
            batchContainer.style.display = 'block';
            progressBar.style.width = '0%';

            let successCount = 0;
            const total = batchDishes.length;

            for (let i = 0; i < total; i++) {
                const dish = batchDishes[i];
                batchStatus.innerText = `Plat ${i + 1} sur ${total} généré en cours... (${dish.name})`;

                try {
                    const res = await fetch(ebspSettings.restUrl + 'generate-batch-image', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': ebspSettings.nonce
                        },
                        body: JSON.stringify({ dish_name: dish.name, slug: dish.slug })
                    });

                    if (res.ok) {
                        successCount++;
                    }
                } catch(e) {
                    console.error('Batch error for ' + dish.name, e);
                }

                // Update progress visually
                progressBar.style.width = `${((i + 1) / total) * 100}%`;

                // Rate limiting delay of 1.5s
                if (i < total - 1) {
                    await sleep(1500);
                }
            }

            batchStatus.innerText = `Terminé ! ${successCount} sur ${total} images générées et sauvegardées.`;
            btnBatchGenerate.disabled = false;
        });
    }

    fetchPresets();
});
