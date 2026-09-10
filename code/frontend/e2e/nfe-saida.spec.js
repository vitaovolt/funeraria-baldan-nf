import { expect, test } from '@playwright/test'

test('menu Fiscal e tela de NF-e de saída dos produtos', async ({ page }) => {
  await page.goto('/login')
  await page.getByTestId('login-email').fill('operador')
  await page.getByTestId('login-password').fill('password')
  await page.getByTestId('login-submit').click()
  await expect(page.getByTestId('page-home')).toBeVisible()

  await expect(page.getByLabel('Principal').getByRole('link', { name: 'Fiscal' })).toBeVisible()
  await expect(page.getByRole('link', { name: 'NF-e saída' }).first()).toBeVisible()

  await page.getByLabel('Principal').getByRole('link', { name: 'Caixa' }).click()
  await expect(page.getByTestId('page-caixa')).toBeVisible()
  if (await page.getByTestId('abrir-caixa').isVisible()) {
    await page.getByTestId('abrir-caixa').click()
    await expect(page.getByTestId('caixa-status')).toContainText(/Caixa aberto/i)
  }

  await page.goto('/fiscal/nfe-saida')
  await expect(page.getByTestId('page-nfe-saida')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'NF-e de saída' })).toBeVisible()
  await expect(page.getByTestId('emitir-nfe-saida')).toBeDisabled()

  await page.getByTestId('busca-produto-nfe').fill('Muleta')
  await expect(page.getByTestId('lista-produtos-nfe').locator('button').first()).toBeVisible()
  await page.getByTestId('lista-produtos-nfe').locator('button').first().click()
  await expect(page.getByTestId('carrinho-nfe')).toBeVisible()
})
