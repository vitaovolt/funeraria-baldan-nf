import { expect, test } from '@playwright/test'

async function login(page) {
  await loginAs(page, 'operador@baldan.local', 'password')
}

async function loginAs(page, email, password) {
  await page.goto('/login')
  await expect(page.getByTestId('page-login')).toBeVisible()
  const emailInput = page.getByTestId('login-email')
  const passwordInput = page.getByTestId('login-password')
  await emailInput.click()
  await emailInput.fill(email)
  await expect(emailInput).toHaveValue(email)
  await passwordInput.click()
  await passwordInput.fill(password)
  await page.getByTestId('login-submit').click()
  await expect(page.getByTestId('page-home')).toBeVisible({ timeout: 20_000 })
}

function nav(page) {
  return page.getByLabel('Principal')
}

test('F4 módulos: cadastros, estoque, consignado, caixa, config', async ({ page }) => {
  await login(page)

  await nav(page).getByRole('link', { name: 'Produtos' }).click()
  await expect(page.getByTestId('page-produtos')).toBeVisible()
  await page.getByRole('link', { name: /Novo produto/i }).click()
  await expect(page.getByTestId('produto-form')).toBeVisible()
  const barcode = `7899${Date.now().toString().slice(-9)}`
  await page.getByLabel('Código de barras').fill(barcode)
  await page.getByLabel('Descrição').fill('Produto F4 E2E')
  await page.getByLabel('Custo').fill('10')
  await page.getByLabel('Preço de venda').fill('25')
  await page.getByLabel('Estoque atual').fill('5')
  await page.getByTestId('produto-salvar').click()
  await expect(page.getByTestId('page-produtos')).toBeVisible()
  await expect(page.getByText('Produto F4 E2E')).toBeVisible()

  await nav(page).getByRole('link', { name: 'Clientes' }).click()
  await expect(page.getByTestId('page-clientes')).toBeVisible()
  await page.getByRole('link', { name: /Novo cliente/i }).click()
  await expect(page.getByTestId('cliente-form')).toBeVisible()
  await page.getByLabel('Documento').fill(`9${Date.now().toString().slice(-10)}`)
  await page.getByRole('textbox', { name: 'Nome', exact: true }).fill('Cliente F4 E2E')
  await page.getByTestId('cliente-salvar').click()
  await expect(page.getByTestId('page-clientes')).toBeVisible()
  await expect(page.getByText('Cliente F4 E2E')).toBeVisible()

  await nav(page).getByRole('link', { name: 'Estoque' }).click()
  await expect(page.getByTestId('page-estoque')).toBeVisible()
  const estoqueProduto = page.locator('[data-testid="page-estoque"] select').first()
  const estoqueValor = await estoqueProduto.locator('option', { hasText: 'Produto F4 E2E' }).first().getAttribute('value')
  await estoqueProduto.selectOption(estoqueValor)
  await page.locator('[data-testid="page-estoque"] select').nth(1).selectOption('entrada')
  await page.getByPlaceholder('Quantidade').fill('2')
  await page.getByTestId('estoque-salvar').click()
  await expect(page.getByText('Estoque ajustado')).toBeVisible()

  await nav(page).getByRole('link', { name: 'Caixa' }).click()
  await expect(page.getByTestId('page-caixa')).toBeVisible()
  if (await page.getByTestId('abrir-caixa').isVisible()) {
    await page.getByTestId('abrir-caixa').click()
    await expect(page.getByTestId('caixa-status')).toContainText(/Caixa aberto/i)
  }

  await nav(page).getByRole('link', { name: 'Consignado' }).click()
  await expect(page.getByTestId('page-consignado')).toBeVisible()
  const consCliente = page.locator('[data-testid="page-consignado"] select').first()
  const consProduto = page.locator('[data-testid="page-consignado"] select').nth(1)
  await consCliente.selectOption(await consCliente.locator('option', { hasText: 'Cliente F4 E2E' }).first().getAttribute('value'))
  await consProduto.selectOption(await consProduto.locator('option', { hasText: 'Produto F4 E2E' }).first().getAttribute('value'))
  await page.locator('[data-testid="page-consignado"] input[type="number"]').fill('1')
  await page.getByTestId('consignado-criar').click()
  await expect(page.getByText('Consignado criado')).toBeVisible()
  await page.getByTestId('consignado-devolver').first().click()
  await expect(page.getByText('Devolução registrada')).toBeVisible()

  await nav(page).getByRole('link', { name: 'Caixa' }).click()
  await page.getByTestId('sangria-valor').fill('5')
  await page.getByPlaceholder('Motivo').fill('Troco E2E')
  await page.getByTestId('sangria-salvar').click()
  await expect(page.getByText('Sangria registrada')).toBeVisible()

  page.once('dialog', (d) => d.accept())
  await page.getByTestId('fechar-caixa').click()
  await expect(page.getByTestId('caixa-status')).toContainText(/Nenhum caixa aberto/i)
  await expect(page.getByTestId('imprimir-fechamento')).toBeVisible()

  await nav(page).getByRole('link', { name: 'Config' }).click()
  await expect(page.getByTestId('page-config')).toBeVisible()
  await expect(page.getByTestId('config-somente-leitura')).toBeVisible()
  await expect(page.getByLabel('CNPJ')).not.toHaveValue('')

  await page.getByTestId('logout-button').click()
  await expect(page.getByTestId('page-login')).toBeVisible()
  await loginAs(page, 'admin@baldan.local', 'password')

  await nav(page).getByRole('link', { name: 'Config' }).click()
  await expect(page.getByTestId('page-config')).toBeVisible()
  await expect(page.getByLabel('CNPJ')).not.toHaveValue('')
  await page.getByLabel('Razão social').fill('Funeraria Baldan E2E LTDA')
  const [putRes] = await Promise.all([
    page.waitForResponse((r) => r.url().includes('/configuracao-fiscal') && r.request().method() === 'PUT'),
    page.getByTestId('config-salvar').click(),
  ])
  expect(putRes.ok()).toBeTruthy()
  await expect(page.getByText(/fiscal salva/i)).toBeVisible()

  await page.locator('input[type="file"]').setInputFiles({
    name: 'dummy.pfx',
    mimeType: 'application/x-pkcs12',
    buffer: Buffer.from('fake-pfx'),
  })
  await page.getByTestId('certificado-upload').click()
  await expect(page.getByText(/Certificado A1 enviado/i)).toBeVisible({ timeout: 15_000 })
})
