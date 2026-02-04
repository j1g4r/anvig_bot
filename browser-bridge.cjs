const puppeteer = require('puppeteer');

(async () => {
    const args = process.argv.slice(2);
    const command = args[0]; // 'browse', 'screenshot', 'content'
    const url = args[1];
    
    if (!url) {
        console.error(JSON.stringify({ error: 'URL required' }));
        process.exit(1);
    }

    try {
        const browser = await puppeteer.launch({
            headless: "new",
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        const page = await browser.newPage();
        
        // Context: Desktop
        await page.setViewport({ width: 1280, height: 800 });
        
        await page.goto(url, { waitUntil: 'networkidle0', timeout: 30000 });

        let result = {};

        if (command === 'content') {
            // Intelligent Content Extraction
            const data = await page.evaluate(() => {
                // Remove noise
                const noise = document.querySelectorAll('script, style, nav, footer, header, aside, .ad, .popup');
                noise.forEach(el => el.remove());

                // Try to find main content
                const main = document.querySelector('article, main, .content, #content, .post');
                if (main) return main.innerText;
                
                // Fallback: Body
                return document.body.innerText;
            });
            
            // Clean up whitespace
            const cleanText = data.replace(/\s\s+/g, ' ').trim();
            result = { content: cleanText.substring(0, 15000), title: await page.title() }; // Increased limit
        } else if (command === 'screenshot') {
            const path = `storage/app/public/browser_screenshots/${Date.now()}.png`;
            await page.screenshot({ path: path, fullPage: true });
            result = { screenshot_path: path, url: url };
        } else {
             result = { title: await page.title(), url: await page.url() };
        }

        console.log(JSON.stringify(result));
        
        await browser.close();
    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    }
})();
