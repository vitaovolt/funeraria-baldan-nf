import { expect, test } from '@playwright/test'

test('fluxo feliz: caixa → PDV → finalizar sem NFC-e (módulo fiscal off)', async ({ page }) => {
  await page.goto('/login')
  await page.getByTestId('login-email').fill('operador')
  await page.getByTestId('login-password').fill('password')
  await page.getByTestId('login-submit').click()
  await expect(page.getByTestId('page-home')).toBeVisible()

  await page.getByTestId('home-caixa').click()
  await expect(page.getByTestId('page-caixa')).toBeVisible()

  const status = page.getByTestId('caixa-status')
  if (await page.getByTestId('abrir-caixa').isVisible()) {
    await page.getByTestId('abrir-caixa').click()
    await expect(status).toContainText(/Caixa aberto/i)
  }

  await page.getByTestId('ir-pdv').click()
  await expect(page.getByTestId('page-pdv')).toBeVisible()

  await page.getByTestId('busca-produto').fill('Muleta')
  await expect(page.getByTestId('lista-produtos').locator('button').first()).toBeVisible()
  await page.getByTestId('lista-produtos').locator('button').first().click()
  await expect(page.getByTestId('carrinho')).toBeVisible()

  page.on('popup', (popup) => popup.close().catch(() => {}))
  await page.getByTestId('pagar-emitir').click()
  await expect(page.getByTestId('modal-nfce')).toHaveCount(0)
  await expect(page.getByTestId('toast-success')).toBeVisible({ timeout: 20_000 })
  await expect(page.getByTestId('page-pdv')).toBeVisible()

  await expect(page.getByLabel('Principal').getByRole('link', { name: 'Notas' })).toHaveCount(0)
})
