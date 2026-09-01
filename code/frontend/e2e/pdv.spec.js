import { expect, test } from '@playwright/test'

test('fluxo feliz T3–T6: caixa → PDV → pagar e emitir NFC-e', async ({ page }) => {
  await page.goto('/login')
  await page.getByTestId('login-email').fill('operador@baldan.local')
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
  await expect(page.getByTestId('modal-nfce')).toBeVisible()
  await page.getByTestId('nfce-sim').click()
  await expect(page.getByTestId('toast-success')).toBeVisible({ timeout: 20_000 })
  await expect(page.getByTestId('page-pdv')).toBeVisible()

  await page.getByRole('link', { name: 'Notas' }).click()
  await expect(page.getByTestId('page-notas')).toBeVisible()
  await expect(page.getByText(/autorizada/i).first()).toBeVisible()
})
