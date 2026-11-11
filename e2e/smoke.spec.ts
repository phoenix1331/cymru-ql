import { expect, type Page, test } from '@playwright/test';

function useApi(page: Page, source: 'rest' | 'graphql') {
    return page.addInitScript((value) => {
        window.localStorage.setItem('cymruql.settings', JSON.stringify({ source: value, batchload: true }));
    }, source);
}

const panel = (page: Page) => page.getByRole('complementary', { name: 'What this cost' });
const scoreboardCell = (page: Page, row: string, column: 1 | 2) =>
    panel(page).getByRole('row', { name: new RegExp(`^${row}`) }).getByRole('cell').nth(column - 1);

test('club page loads over GraphQL in one request', async ({ page }) => {
    await useApi(page, 'graphql');
    await page.goto('/clubs/caernarfon-town');

    await expect(page.getByRole('heading', { level: 1, name: 'Caernarfon Town' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Home ground' })).toBeVisible();
    await expect(page.getByRole('table', { name: 'Cymru Premier table' })).toBeVisible();
    await expect(panel(page).getByRole('heading', { name: '1 GraphQL request' })).toBeVisible();
    await expect(scoreboardCell(page, 'Requests', 2)).toHaveText(/^1\b/);
});

test('club page loads the same screen over REST with more requests', async ({ page }) => {
    await useApi(page, 'graphql');
    await page.goto('/clubs/caernarfon-town');
    await expect(scoreboardCell(page, 'Requests', 2)).toHaveText(/^1\b/);

    await panel(page).locator('label', { hasText: /^REST$/ }).click();

    await expect(page.getByRole('heading', { level: 1, name: 'Caernarfon Town' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Away trips' })).toBeVisible();
    await expect(panel(page).getByText('GET /api/clubs/caernarfon-town', { exact: true }).first()).toBeVisible();

    const restRequests = parseInt((await scoreboardCell(page, 'Requests', 1).textContent()) ?? '0', 10);
    expect(restRequests).toBeGreaterThan(5);
    await expect(scoreboardCell(page, 'Requests', 2)).toHaveText(/^1\b/);
});

test('league page shows the table', async ({ page }) => {
    await useApi(page, 'rest');
    await page.goto('/leagues/cymru-south');

    await expect(page.getByRole('heading', { level: 1, name: 'Cymru South' })).toBeVisible();
    await expect(page.getByRole('table', { name: /Cymru South .* table/ }).getByRole('row')).toHaveCount(17);
    await expect(panel(page).getByRole('heading', { name: '2 REST requests' })).toBeVisible();
});
