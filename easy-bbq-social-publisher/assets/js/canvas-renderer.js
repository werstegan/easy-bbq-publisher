class EBSPCanvasRenderer {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.width = 1080;
        this.height = 1920;
    }

    async render(data) {
        this.ctx.clearRect(0, 0, this.width, this.height);

        // Background
        this.ctx.fillStyle = '#2c3e50';
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Grid/Texture simulation
        this.ctx.strokeStyle = 'rgba(255,255,255,0.05)';
        for(let i=0; i<this.width; i+=50) {
            this.ctx.beginPath();
            this.ctx.moveTo(i, 0);
            this.ctx.lineTo(i, this.height);
            this.ctx.stroke();
        }

        // Header
        this.ctx.fillStyle = '#e74c3c';
        this.ctx.fillRect(0, 0, this.width, 250);

        this.ctx.fillStyle = '#fff';
        this.ctx.font = 'bold 80px sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText('EASY BBQ', this.width / 2, 120);

        this.ctx.font = 'bold 60px sans-serif';
        this.ctx.fillStyle = '#f1c40f';
        this.ctx.fillText(`Menú de ${data.day || 'Jour'}`, this.width / 2, 200);

        // Body Content
        let yOffset = 400;

        // Starter
        await this.drawSection('Starter (De Primero)', data.starter_title, data.images?.starter, yOffset);
        yOffset += 300;

        // Main 1
        await this.drawSection('Main Course 1', data.main1_title, data.images?.main1, yOffset);
        yOffset += 300;

        // Main 2
        await this.drawSection('Main Course 2', data.main2_title, data.images?.main2, yOffset);
        yOffset += 300;

        // Drink
        await this.drawSection('Drink (Bebida)', data.drink, null, yOffset);

        // Price Badge
        this.ctx.beginPath();
        this.ctx.arc(this.width / 2, 1600, 120, 0, 2 * Math.PI);
        this.ctx.fillStyle = '#e74c3c';
        this.ctx.fill();
        this.ctx.lineWidth = 10;
        this.ctx.strokeStyle = '#f1c40f';
        this.ctx.stroke();

        this.ctx.fillStyle = '#fff';
        this.ctx.font = 'bold 70px sans-serif';
        this.ctx.fillText(`${data.price || '22'} CHF`, this.width / 2, 1625);

        // Footer
        this.ctx.fillStyle = '#34495e';
        this.ctx.fillRect(0, this.height - 150, this.width, 150);
        this.ctx.fillStyle = '#fff';
        this.ctx.font = '40px sans-serif';
        this.ctx.fillText('📍 Route de la Plage 1, 1400 Yverdon-les-Bains | @easybbq.ch', this.width / 2, this.height - 60);
    }

    async drawSection(label, value, imageUrl, y) {
        this.ctx.fillStyle = '#f1c40f';
        this.ctx.font = 'bold 45px sans-serif';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(label, this.width / 2, y);

        let textY = y + 70;

        if (imageUrl) {
            try {
                const img = await this.loadImage(imageUrl);
                // Draw a circular frame for the dish
                this.ctx.save();
                this.ctx.beginPath();
                this.ctx.arc(this.width / 2, y + 150, 100, 0, Math.PI * 2);
                this.ctx.clip();
                this.ctx.drawImage(img, this.width / 2 - 100, y + 50, 200, 200);
                this.ctx.restore();

                // Add yellow/red accent border
                this.ctx.beginPath();
                this.ctx.arc(this.width / 2, y + 150, 100, 0, Math.PI * 2);
                this.ctx.lineWidth = 5;
                this.ctx.strokeStyle = '#e74c3c';
                this.ctx.stroke();

                textY = y + 290;
            } catch (e) {
                console.error("Failed to load image", e);
            }
        }

        this.ctx.fillStyle = '#ecf0f1';
        this.ctx.font = '55px sans-serif';

        // Simple word wrap
        const maxW = 900;
        let words = (value || '').split(' ');
        let line = '';
        let lineY = textY;

        for(let n = 0; n < words.length; n++) {
            let testLine = line + words[n] + ' ';
            let metrics = this.ctx.measureText(testLine);
            if (metrics.width > maxW && n > 0) {
                this.ctx.fillText(line, this.width / 2, lineY);
                line = words[n] + ' ';
                lineY += 60;
            } else {
                line = testLine;
            }
        }
        this.ctx.fillText(line, this.width / 2, lineY);
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
