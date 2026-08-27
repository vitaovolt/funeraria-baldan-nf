import { expect, test } from '@playwright/test'

test('F5 hardening: operador vê config em leitura; PDV paga com Processando', async ({ page }) => {
  await page.goto('/login')
  await page.getByTestId('login-email').fill('operador@baldan.local')
  await page.getByTestId('login-password').fill('password')
  await page.getByTestId('login-submit').click()
  await expect(page.getByTestId('page-home')).toBeVisible()

  await page.getByLabel('Principal').getByRole('link', { name: 'Config' }).click()
  await expect(page.getByTestId('page-config')).toBeVisible()
  await expect(page.getByTestId('config-somente-leitura')).toBeVisible()
  await expect(page.getByTestId('config-salvar')).toHaveCount(0)

  await page.getByLabel('Principal').getByRole('link', { name: 'Caixa' }).click()
  await expect(page.getByTestId('page-caixa')).toBeVisible()
  await expect(page.getByTestId('caixa-status')).toBeVisible()
  const abrir = page.getByTestId('abrir-caixa')
  if (await abrir.isVisible()) {
    await abrir.click()
  }
  await expect(page.getByTestId('caixa-status')).toContainText(/Caixa aberto/i)

  await page.getByLabel('Principal').getByRole('link', { name: 'PDV' }).click()
  await expect(page.getByTestId('page-pdv')).toBeVisible()
  await page.getByTestId('busca-produto').fill('Muleta')
  await expect(page.getByTestId('lista-produtos').locator('button').first()).toBeVisible()
  await page.getByTestId('lista-produtos').locator('button').first().click()
  await page.getByTestId('pagar-emitir').click()
  await expect(page.getByTestId('toast-success')).toBeVisible({ timeout: 20_000 })
})
