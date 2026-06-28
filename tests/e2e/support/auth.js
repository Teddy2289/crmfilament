export const e2eUser = {
    email: process.env.PLAYWRIGHT_E2E_EMAIL ?? 'a.florek@ns-conseil.com',
    password: process.env.PLAYWRIGHT_E2E_PASSWORD ?? 'changeme123',
};

export async function loginToNsConseil(page, user = e2eUser, redirectPath = '/ns-conseil') {
    const loginTimeout = 60_000;
    const params = new URLSearchParams({
        email: user.email,
        redirect: redirectPath,
    });

    await page.goto(`/__e2e/login?${params.toString()}`, {
        timeout: loginTimeout,
        waitUntil: 'commit',
    });
}
