const puppeteer = require('puppeteer')
const yargs = require('yargs/yargs')
const { hideBin } = require('yargs/helpers')
const fs = require('fs')

console.log(process.argv)
const argv = yargs(hideBin(process.argv))
    .command('puppeteer <url> <output>', 'print a webpage', yargs => {
        yargs.positional('file', {
            describe: 'URL to generate PDF from',
            type: 'string',
        })
        .option('stylesheet', {
            describe: 'Path to the stylesheet',
            type: 'string',
        })
        .option('pdfOptions', {
            describe: 'json encoded object of pdfOptions',
            type: 'string'
        })
        .option('header', {
            describe: 'Path to a header file',
            type: 'string'
        })
        option('footer', {
            describe: 'Path to a footer file',
            type: 'string'
        })
        .demand(['file', 'output'])
    }).argv

const { file } = argv
console.log(file)
console.log(argv)

async function generatePDF(file, options) {
    try {
        let {stylesheet, pdfOptions} = options;
        pdfOptions = JSON.parse(pdfOptions)
        console.log('pdfOptions', pdfOptions)
        console.log('PDF GENERATION STARTED')
        console.log('file', file)
        console.log('options', options)
        const browser = await puppeteer.launch({args: ['--no-sandbox']})
        console.log('browser')
        const page = await browser.newPage()
        console.log('page')
        await page.goto(file, { waitUntil: 'networkidle0' })
        console.log('goto')
        if(stylesheet)
            page.addStyleTag({path: stylesheet});
        console.log(stylesheet);
        await page.pdf({
            headerTemplate: options.header ? fs.readFileSync(options.header, 'utf8') : '',
            footerTemplate: options.footer ? fs.readFileSync(options.footer, 'utf8') : '',
            ...pdfOptions
        })
        console.log('pdf')
        await browser.close()
        console.log('close')
    } catch(error) {
        console.error('[generatePDF]:', error)
    }
}

try {
    generatePDF(argv.file, argv)
        .then(() => console.log('PDF generated successfully'))
        .catch((error) => console.error('PDF generation failed: ', error))
} catch (error) {
    console.error("ERROR", error)
}
