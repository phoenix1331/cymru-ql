import Pusher from 'pusher-js';
import type { ExecutionResult, SubscriptionForwarder } from 'urql';
import { instrumentedFetch } from '@/api/instrumentedFetch';

/** Lighthouse's event name for subscription payloads (Broadcaster::EVENT_NAME). */
const EVENT_NAME = 'lighthouse-subscription';

let pusher: Pusher | null = null;

/** One shared WebSocket connection to Reverb, opened on first use. */
function reverb(): Pusher {
    pusher ??= new Pusher(import.meta.env.VITE_REVERB_APP_KEY, {
        cluster: 'reverb',
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT),
        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        channelAuthorization: { endpoint: '/graphql/subscriptions/auth', transport: 'ajax' },
    });

    return pusher;
}

interface LighthousePayload {
    more: boolean;
    result: ExecutionResult;
}

/**
 * Lighthouse subscriptions over Reverb: POST the subscription like any query,
 * then listen on the private channel Lighthouse returns in
 * extensions.lighthouse_subscriptions for each pushed result.
 */
export const forwardSubscription: SubscriptionForwarder = (request) => ({
    subscribe(sink) {
        let channel: string | null = null;
        let closed = false;

        instrumentedFetch('graphql', '/graphql', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(request),
        })
            .then((response) => response.json() as Promise<ExecutionResult & { extensions?: { lighthouse_subscriptions?: { channel?: string } } }>)
            .then((result) => {
                if (closed) {
                    return;
                }

                sink.next(result);
                channel = result.extensions?.lighthouse_subscriptions?.channel ?? null;

                if (channel === null) {
                    sink.complete();
                    return;
                }

                reverb()
                    .subscribe(channel)
                    .bind(EVENT_NAME, (payload: LighthousePayload) => {
                        sink.next(payload.result);
                        if (!payload.more) {
                            sink.complete();
                        }
                    });
            })
            .catch((error: unknown) => sink.error(error));

        return {
            unsubscribe() {
                closed = true;
                if (channel !== null) {
                    reverb().unsubscribe(channel);
                }
            },
        };
    },
});
