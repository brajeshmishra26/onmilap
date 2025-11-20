function initSubscriptionWidget() {
    const planData = window.subscriptionPlans || [];
    const plans = Array.isArray(planData) ? planData : Object.values(planData);
    if (!plans.length) {
        return;
    }
    console.debug('Subscription widget: booting with', plans.length, 'plans');

    const currencySelect = document.getElementById('currency-select');
    if (!currencySelect) {
        console.error('Subscription widget: missing #currency-select element');
        return;
    }
    const urlParams = new URLSearchParams(window.location.search);
    let exchangeRate = 83; // default fallback so INR toggle is instant
    // Normalize the API base without collapsing the protocol double slash
    const rawApiBase = window.subscriptionApiBase || '/api/';
    const apiBase = rawApiBase.replace(/([^:])\/+/g, '$1/');

    function apiUrl(path) {
        if (!path) {
            return apiBase;
        }
        return apiBase.replace(/\/?$/, '/') + path.replace(/^\//, '');
    }

    async function fetchRate() {
        try {
            const response = await fetch(apiUrl('subscriptions/exchange_rate.php'));
            if (!response.ok) {
                throw new Error('Unable to fetch rate');
            }
            const payload = await response.json();
            if (payload.rate) {
                exchangeRate = parseFloat(payload.rate);
            }
        } catch (err) {
            console.warn('Exchange rate request failed', err);
        }
    }

    function normalize(value) {
        return (value || '').toString().trim().toLowerCase();
    }

    function setActiveCard(card) {
        document.querySelectorAll('#subscription-widget .plan-card').forEach((node) => {
            node.classList.remove('selected-plan');
        });
        if (card) {
            card.classList.add('selected-plan');
        }
    }

    function focusPlanFromQuery() {
        const hint = normalize(urlParams.get('plan'));
        if (!hint) {
            return;
        }
        const targetPlan = plans.find((plan) => normalize(plan.slug) === hint || normalize(plan.name) === hint);
        if (!targetPlan) {
            return;
        }
        const targetCard = document.querySelector(`#subscription-widget .plan-card[data-plan="${targetPlan.slug}"]`);
        if (targetCard) {
            setActiveCard(targetCard);
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function formatPrice(plan, currency) {
        if (currency === 'INR') {
            const base = plan.price_inr !== undefined && plan.price_inr !== null && plan.price_inr !== ''
                ? parseFloat(plan.price_inr)
                : parseFloat(plan.price_usd) * exchangeRate;
            return `₹${base.toFixed(2)}`;
        }
        return `$${parseFloat(plan.price_usd).toFixed(2)}`;
    }

    function renderPrices(currency) {
        document.querySelectorAll('#subscription-widget .plan-card').forEach((card) => {
            const slug = card.getAttribute('data-plan');
            const plan = plans.find((item) => item.slug === slug);
            if (!plan) {
                return;
            }
            const priceEl = card.querySelector('.plan-price');
            if (priceEl) {
                priceEl.textContent = formatPrice(plan, currency);
            }
        });
    }

    async function createOrder(plan) {
        const payload = {
            plan_slug: plan.slug,
            currency: currencySelect.value,
        };

        if (window.currentUserId) {
            payload.user_id = window.currentUserId;
        }

        const response = await fetch(apiUrl('subscriptions/purchase.php'), {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        if (!response.ok) {
            let message = 'Unable to create order';
            try {
                const errorPayload = await response.json();
                if (errorPayload && errorPayload.error) {
                    message = errorPayload.error;
                }
            } catch (jsonError) {
                // ignored so fallback message remains
            }
            throw new Error(message);
        }
        const data = await response.json();
        if (!data.ok) {
            throw new Error(data.error || 'Unable to create order');
        }
        return data;
    }

    function attachEvents() {
        const buttons = document.querySelectorAll('#subscription-widget .plan-card button');
        if (!buttons.length) {
            console.warn('Subscription widget: no plan buttons found');
            return;
        }
        buttons.forEach((button) => {
            button.addEventListener('click', async () => {
                const card = button.closest('.plan-card');
                const slug = card.getAttribute('data-plan');
                const plan = plans.find((item) => item.slug === slug);
                if (!plan) {
                    return;
                }
                setActiveCard(card);
                button.disabled = true;
                button.textContent = 'Processing…';
                try {
                    const orderResponse = await createOrder(plan);
                    if (orderResponse.status === 'completed') {
                        alert('Plan activated successfully.');
                        return;
                    }
                    if (orderResponse.gateway === 'razorpay' && orderResponse.order) {
                        launchRazorpay(orderResponse.order);
                    } else if (orderResponse.gateway === 'paypal' && orderResponse.checkout_url) {
                        window.location.href = orderResponse.checkout_url;
                    }
                } catch (err) {
                    alert(err.message);
                } finally {
                    button.disabled = false;
                    button.textContent = 'Recharge now';
                }
            });
        });
    }

    function launchRazorpay(order) {
        if (typeof Razorpay === 'undefined') {
            console.error('Razorpay library missing');
            return;
        }
        const options = {
            key: order.key || window.razorpayKeyId,
            amount: order.amount,
            currency: order.currency,
            order_id: order.order_id,
            name: 'onMilap',
            description: order.description,
            handler: function (response) {
                fetch(apiUrl('subscriptions/webhook_razorpay.php'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature,
                        razorpay_order_id: response.razorpay_order_id
                    })
                })
                    .then((res) => res.json().catch(() => ({})))
                    .then((result) => {
                        if (result && result.ok) {
                            alert('Payment captured. Plan will reflect shortly.');
                        } else if (result && result.error) {
                            alert(result.error);
                        }
                    })
                    .catch((error) => console.error('Razorpay confirmation failed', error));
            }
        };
        new Razorpay(options).open();
    }

    currencySelect.addEventListener('change', () => renderPrices(currencySelect.value));

    renderPrices(currencySelect.value);
    fetchRate().then(() => renderPrices(currencySelect.value));
    attachEvents();
    focusPlanFromQuery();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSubscriptionWidget);
} else {
    initSubscriptionWidget();
}
