const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

(async () => {
    const keyword = process.argv[2];
    const platform = process.argv[3];
    
    // Launch headless browser
    const browser = await puppeteer.launch({
        headless: "new",
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const page = await browser.newPage();
    
    try {
        if (platform === 'Shopee Malaysia') {
            await page.goto(`https://shopee.com.my/search?keyword=${encodeURIComponent(keyword)}`, { waitUntil: 'networkidle2', timeout: 20000 });
            
            const data = await page.evaluate(() => {
                const item = document.querySelector('div[data-sqe="item"]'); // Shopee's product card wrapper
                if (!item) return null;
                const priceText = item.innerText.match(/RM[0-9,.]+/);
                if (priceText) return { price: parseFloat(priceText[0].replace(/[^0-9.]/g, '')) };
                return null;
            });
            console.log(JSON.stringify(data));

        } else if (platform === 'Lazada Malaysia') {
            await page.goto(`https://www.lazada.com.my/catalog/?q=${encodeURIComponent(keyword)}`, { waitUntil: 'networkidle2', timeout: 20000 });
            
            const data = await page.evaluate(() => {
                const item = document.querySelector('div[data-qa-locator="product-item"]'); // Lazada's product card
                if (!item) return null;
                const priceText = item.innerText.match(/RM[0-9,.]+/);
                if (priceText) return { price: parseFloat(priceText[0].replace(/[^0-9.]/g, '')) };
                return null;
            });
            console.log(JSON.stringify(data));
        }
    } catch (e) {
        // Silently fail and return empty JSON on timeout/captcha block
        console.log(JSON.stringify(null));
    } finally {
        await browser.close();
    }
})();