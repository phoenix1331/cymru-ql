import { updateSettings, useSettings } from '@/api/settings';

/** Turns Lighthouse's relation batch loading off for GraphQL requests, to show the N+1 cost. */
export function BatchLoadSwitch() {
    const { batchload } = useSettings();

    return (
        <div className="rounded-md border border-mist bg-white p-3">
            <label className="flex cursor-pointer items-center justify-between gap-3">
                <span className="font-display text-lg font-semibold">Batch load relations</span>
                <input
                    type="checkbox"
                    role="switch"
                    checked={batchload}
                    onChange={(event) => updateSettings({ batchload: event.target.checked })}
                    className="peer sr-only"
                />
                <span
                    aria-hidden="true"
                    className="relative h-6 w-11 shrink-0 rounded-full bg-mist transition-colors peer-checked:bg-pitch peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-pitch after:absolute after:top-1 after:left-1 after:size-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5"
                />
            </label>
            <p className="mt-2 text-sm text-slate-soft">
                {batchload
                    ? 'On: related records are fetched together, one query per relation.'
                    : 'Off: each relation is fetched one record at a time. Watch the database queries climb.'}
            </p>
        </div>
    );
}
