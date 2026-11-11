import { useId } from 'react';

const PATTERNS = ['plain', 'hoops', 'halves', 'sash', 'chevron'] as const;
const SHIELD = 'M4 4h56v26c0 16-12 25-28 30C16 55 4 46 4 30z';
const SKIP_WORDS = new Set(['town', 'city', 'united', 'fc', 'afc', 'the', 'and', '&']);

/** Stable 32 bit hash so a club always gets the same pattern. */
function hash(text: string): number {
    let value = 2166136261;
    for (const char of text) {
        value = Math.imul(value ^ char.charCodeAt(0), 16777619);
    }
    return value >>> 0;
}

export function initials(name: string): string {
    const words = name.split(/\s+/).filter((word) => !SKIP_WORDS.has(word.toLowerCase()));
    const letters = (words.length > 0 ? words : name.split(/\s+/)).map((word) => word[0] ?? '');
    return letters.slice(0, 3).join('').toUpperCase();
}

/** Light or dark text, whichever reads better on the club colour. */
function contrastText(hex: string): string {
    const rgb = hex.replace('#', '').match(/.{2}/g)?.map((part) => parseInt(part, 16) / 255) ?? [0, 0, 0];
    const [r = 0, g = 0, b = 0] = rgb.map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b > 0.4 ? '#2e3a40' : '#ffffff';
}

/**
 * A generated placeholder crest: club colour, a pattern picked from the slug
 * and the club's initials. No real club badges are used.
 */
export function Crest({ slug, name, colour, size = 40 }: { slug: string; name: string; colour: string; size?: number }) {
    const clipId = useId();
    const pattern = PATTERNS[hash(slug) % PATTERNS.length];
    const text = contrastText(colour);
    const letters = initials(name);
    const overlay = 'rgba(255,255,255,0.22)';

    return (
        <svg width={size} height={size} viewBox="0 0 64 64" role="img" aria-label={`${name} crest`} className="shrink-0">
            <clipPath id={clipId}>
                <path d={SHIELD} />
            </clipPath>
            <g clipPath={`url(#${clipId})`}>
                <rect width="64" height="64" fill={colour} />
                {pattern === 'hoops' && [14, 30, 46].map((y) => <rect key={y} y={y} width="64" height="7" fill={overlay} />)}
                {pattern === 'halves' && <rect x="32" width="32" height="64" fill={overlay} />}
                {pattern === 'sash' && <path d="M0 10 L10 0 L64 54 L54 64z" fill={overlay} />}
                {pattern === 'chevron' && <path d="M0 20 L32 40 L64 20 L64 30 L32 50 L0 30z" fill={overlay} />}
            </g>
            <path d={SHIELD} fill="none" stroke="#2e3a40" strokeOpacity="0.35" strokeWidth="2" />
            <text
                x="32"
                y="36"
                textAnchor="middle"
                dominantBaseline="middle"
                fill={text}
                fontFamily="'Barlow Condensed', 'Arial Narrow', sans-serif"
                fontWeight="700"
                fontSize={letters.length > 2 ? 18 : 22}
            >
                {letters}
            </text>
        </svg>
    );
}
