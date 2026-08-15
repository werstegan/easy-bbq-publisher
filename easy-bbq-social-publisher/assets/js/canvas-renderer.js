class EBSPCanvasRenderer {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.width = 1080;
        this.height = 1920;
    }

    async render(data) {
        this.ctx.clearRect(0, 0, this.width, this.height);

        // --- A. Background & Global Styles ---
        this.ctx.fillStyle = '#161616'; // Dark charcoal/slate
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Warm amber ember glow at the top
        const grd = this.ctx.createRadialGradient(this.width / 2, 0, 0, this.width / 2, 0, 600);
        grd.addColorStop(0, "rgba(255, 69, 0, 0.2)");
        grd.addColorStop(1, "rgba(22, 22, 22, 0)");
        this.ctx.fillStyle = grd;
        this.ctx.fillRect(0, 0, this.width, 600);

        // --- B. Header Area (Y: 0 to 450px) ---
        // Top Brand Logo
        this.ctx.fillStyle = '#E52D27'; // Red
        this.ctx.font = 'bold 90px "Arial Black", Impact, sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('EASY BBQ', this.width / 2 - 100, 120);

        this.ctx.fillStyle = '#FFD200'; // Yellow
        this.ctx.fillText('LATINO', this.width / 2 + 180, 120);

        // White shadow/outline effect for brand
        this.ctx.lineWidth = 2;
        this.ctx.strokeStyle = '#FFFFFF';
        this.ctx.strokeText('EASY BBQ', this.width / 2 - 100, 120);
        this.ctx.strokeText('LATINO', this.width / 2 + 180, 120);

        // Tagline Badge (Top Right) - Simulated Arched Text
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = '20px Montserrat, sans-serif';
        this.ctx.textAlign = 'right';
        this.ctx.fillText('DONDE COMER BIEN SE VUELVE UNA COSTUMBRE', this.width - 40, 60);

        // Ribbon / Main Day Title (Y: 340px to 440px)
        this.ctx.fillStyle = '#B31217'; // Dark Red Ribbon
        this.ctx.fillRect(100, 340, this.width - 200, 100);
        this.ctx.strokeStyle = '#FFD200';
        this.ctx.lineWidth = 6;
        this.ctx.strokeRect(100, 340, this.width - 200, 100);

        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 54px "Arial Black", Impact, sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.strokeStyle = '#FFFFFF';
        this.ctx.lineWidth = 2;
        const dayText = `MENÚ DE ${(data.day || 'JOUR').toUpperCase()}`;
        this.ctx.fillText(dayText, this.width / 2, 390);
        this.ctx.strokeText(dayText, this.width / 2, 390);
        this.ctx.textBaseline = 'alphabetic'; // Reset baseline

        // --- C. Starter Section ("De Primero") (Y: 480px to 740px) ---
        // Label Badge
        this.ctx.fillStyle = '#000000';
        this.ctx.fillRect(80, 480, 300, 60);
        this.ctx.strokeStyle = '#FFD200';
        this.ctx.lineWidth = 4;
        this.ctx.strokeRect(80, 480, 300, 60);

        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = 'bold 30px Montserrat, sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('DE PRIMERO:', 230, 522);

        // Starter Dish Title
        this.ctx.textAlign = 'left';
        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 38px "Arial Black", sans-serif';
        this.wrapText(data.starter_title || 'Starter Title', 80, 600, 500, 45);

        // Starter Image Frame
        await this.drawDishImage(data.images?.starter, 720, 610, 110, true);

        // Drink Vignette
        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 22px Caveat, cursive, sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('BEBIDA REFRESCANTE INCLUIDA', 880, 750);

        // --- D. Main Course Section ("De Segundo : A Elegir") (Y: 770px to 1420px) ---
        // Section Banner
        this.ctx.fillStyle = '#FFD200';
        this.ctx.fillRect(80, 790, this.width - 160, 70);
        this.ctx.fillStyle = '#B31217';
        this.ctx.font = 'bold 40px "Arial Black", sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('DE SEGUNDO: A ELEGIR', this.width / 2, 840);

        // Left Dish (Main 1)
        await this.drawDishImage(data.images?.main1, 280, 1020, 160, true);
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = 'bold 34px Montserrat, sans-serif';
        this.ctx.textAlign = 'center';
        this.wrapText(data.main1_title || 'Main Course 1', 280, 1220, 400, 40);

        // Center Separator "Ó"
        this.ctx.beginPath();
        this.ctx.arc(540, 1020, 40, 0, Math.PI * 2);
        this.ctx.fillStyle = '#B31217';
        this.ctx.fill();
        this.ctx.lineWidth = 4;
        this.ctx.strokeStyle = '#FFFFFF';
        this.ctx.stroke();
        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 40px "Arial Black", sans-serif';
        this.ctx.fillText('Ó', 540, 1035);

        // Right Dish (Main 2)
        await this.drawDishImage(data.images?.main2, 800, 1020, 160, true);
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = 'bold 34px Montserrat, sans-serif';
        this.ctx.textAlign = 'center';
        this.wrapText(data.main2_title || 'Main Course 2', 800, 1220, 400, 40);

        // --- E. Price Badge (Y: 1280px to 1460px) ---
        this.ctx.beginPath();
        this.ctx.arc(this.width / 2, 1370, 100, 0, Math.PI * 2);
        this.ctx.fillStyle = '#B31217';
        this.ctx.fill();
        this.ctx.lineWidth = 8;
        this.ctx.strokeStyle = '#FFD200';
        this.ctx.stroke();

        this.ctx.beginPath();
        this.ctx.arc(this.width / 2, 1370, 85, 0, Math.PI * 2);
        this.ctx.lineWidth = 2;
        this.ctx.strokeStyle = '#FFFFFF';
        this.ctx.stroke();

        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = 'bold 80px "Arial Black", sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(`${data.price || '22'}`, this.width / 2, 1390);

        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 32px Montserrat, sans-serif';
        this.ctx.fillText('CHF', this.width / 2, 1430);

        // --- F. Call-to-Action & Footer Area (Y: 1480px to 1920px) ---
        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 50px Caveat, cursive, sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('¡Ven y visítanos! Te esperamos.', this.width / 2, 1560);

        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = '28px Montserrat, sans-serif';
        this.ctx.fillText('donde comer bien se vuelve una costumbre', this.width / 2, 1610);

        // Footer left Box
        this.ctx.fillStyle = '#000000';
        this.ctx.fillRect(40, 1680, 500, 180);
        this.ctx.strokeStyle = '#FFD200';
        this.ctx.lineWidth = 2;
        this.ctx.strokeRect(40, 1680, 500, 180);

        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 30px "Arial Black", sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('📍 UBICACIÓN:', 290, 1750);
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = 'bold 24px Montserrat, sans-serif';
        this.ctx.fillText('RUE DE CHÊNE 13', 290, 1790);
        this.ctx.fillText('1020 RENENS', 290, 1830);

        // Footer Right Box
        this.ctx.fillStyle = '#000000';
        this.ctx.fillRect(560, 1680, 480, 180);
        this.ctx.strokeRect(560, 1680, 480, 180);

        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = 'bold 36px "Arial Black", sans-serif';
        this.ctx.fillText('EASY BBQ LATINO', 800, 1750);
        this.ctx.fillStyle = '#FFD200';
        this.ctx.font = 'bold 28px Montserrat, sans-serif';
        this.ctx.fillText('@easybbqlatino', 800, 1800);

        // Tiny flag motif at bottom right (Ecuador)
        this.ctx.fillStyle = '#FFD100'; // Yellow
        this.ctx.fillRect(750, 1820, 100, 10);
        this.ctx.fillStyle = '#00148E'; // Blue
        this.ctx.fillRect(750, 1830, 100, 10);
        this.ctx.fillStyle = '#EF3340'; // Red
        this.ctx.fillRect(750, 1840, 100, 10);
    }

    async drawDishImage(imageUrl, cx, cy, radius, useRing) {
        let imgToDraw = null;

        if (imageUrl) {
            try {
                imgToDraw = await this.loadImage(imageUrl);
            } catch (e) {
                console.error("Failed to load custom image, using placeholder", e);
            }
        }

        this.ctx.save();
        this.ctx.beginPath();
        this.ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        this.ctx.closePath();
        this.ctx.clip();

        if (imgToDraw) {
            this.ctx.drawImage(imgToDraw, cx - radius, cy - radius, radius * 2, radius * 2);
        } else {
            // Draw placeholder appetizing pattern if no image
            this.ctx.fillStyle = '#444';
            this.ctx.fillRect(cx - radius, cy - radius, radius * 2, radius * 2);

            // Draw grid pattern as placeholder
            this.ctx.strokeStyle = '#666';
            this.ctx.lineWidth = 4;
            for(let i = -radius; i < radius; i+=20) {
                this.ctx.beginPath();
                this.ctx.moveTo(cx + i, cy - radius);
                this.ctx.lineTo(cx + i, cy + radius);
                this.ctx.stroke();

                this.ctx.beginPath();
                this.ctx.moveTo(cx - radius, cy + i);
                this.ctx.lineTo(cx + radius, cy + i);
                this.ctx.stroke();
            }
            // Add a subtle fork/knife or "plato" icon simulation
            this.ctx.fillStyle = '#999';
            this.ctx.font = 'bold 40px sans-serif';
            this.ctx.textAlign = 'center';
            this.ctx.fillText('🍽️', cx, cy + 15);
        }

        this.ctx.restore();

        if (useRing) {
            this.ctx.beginPath();
            this.ctx.arc(cx, cy, radius, 0, Math.PI * 2);
            this.ctx.lineWidth = 6;
            this.ctx.strokeStyle = '#FFD200'; // Golden ring
            this.ctx.stroke();
        }
    }

    wrapText(text, x, y, maxWidth, lineHeight) {
        const words = (text || '').split(' ');
        let line = '';
        let currentY = y;

        for (let n = 0; n < words.length; n++) {
            let testLine = line + words[n] + ' ';
            let metrics = this.ctx.measureText(testLine);

            if (metrics.width > maxWidth && n > 0) {
                this.ctx.fillText(line, x, currentY);
                line = words[n] + ' ';
                currentY += lineHeight;
            } else {
                line = testLine;
            }
        }
        this.ctx.fillText(line, x, currentY);
    }

    loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }
}

window.EBSPCanvasRenderer = EBSPCanvasRenderer;
