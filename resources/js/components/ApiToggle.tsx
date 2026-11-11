import { type ApiSource, updateSettings, useSettings } from '@/api/settings';

const OPTIONS: { value: ApiSource; label: string; colour: string }[] = [
    { value: 'rest', label: 'REST', colour: 'bg-rest' },
    { value: 'graphql', label: 'GraphQL', colour: 'bg-graphql' },
];

/** Chooses which API every screen loads its data from. */
export function ApiToggle() {
    const { source } = useSettings();

    return (
        <fieldset>
            <legend className="mb-2 text-sm text-slate-soft">Load this screen with</legend>
            <div className="grid grid-cols-2 rounded-md border border-mist bg-white p-1">
                {OPTIONS.map((option) => {
                    const selected = option.value === source;

                    return (
                        <label
                            key={option.value}
                            className={`cursor-pointer rounded px-3 py-2 text-center font-display text-xl font-semibold has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-pitch ${selected ? `${option.colour} text-white` : 'text-slate-soft hover:text-slate'}`}
                        >
                            <input
                                type="radio"
                                name="api-source"
                                value={option.value}
                                checked={selected}
                                onChange={() => updateSettings({ source: option.value })}
                                className="sr-only"
                            />
                            {option.label}
                        </label>
                    );
                })}
            </div>
        </fieldset>
    );
}
