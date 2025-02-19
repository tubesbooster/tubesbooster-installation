const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  const url = process.argv[2]; // URL passed as a command line argument

  await page.goto(url, { waitUntil: 'domcontentloaded' });
  const content = await page.content();

  console.log(content);

  await browser.close();
})();