<template>
    <!--
        Decorative brand artwork: three tilted orbits around a core, with task "cards" travelling on them.
        Colours come from currentColor (white on the brand panel) plus the comet accent.
    -->
    <svg
        viewBox="0 0 320 200"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <g stroke="currentColor" stroke-opacity=".28" stroke-width="1.5">
            <ellipse
                cx="160"
                cy="100"
                rx="150"
                ry="52"
                transform="rotate(-12 160 100)"
            />
            <ellipse
                cx="160"
                cy="100"
                rx="108"
                ry="36"
                transform="rotate(-12 160 100)"
                stroke-dasharray="4 6"
            />
            <ellipse
                cx="160"
                cy="100"
                rx="66"
                ry="22"
                transform="rotate(-12 160 100)"
            />
        </g>
        <circle cx="160" cy="100" r="22" fill="currentColor" />
        <circle
            cx="160"
            cy="100"
            r="34"
            stroke="currentColor"
            stroke-opacity=".15"
            stroke-width="10"
        />
        <!-- task cards on the orbits -->
        <g>
            <rect
                x="22"
                y="104"
                width="58"
                height="34"
                rx="8"
                fill="currentColor"
                fill-opacity=".14"
                stroke="currentColor"
                stroke-opacity=".35"
            />
            <rect
                x="30"
                y="113"
                width="30"
                height="4"
                rx="2"
                fill="currentColor"
                fill-opacity=".8"
            />
            <rect
                x="30"
                y="123"
                width="20"
                height="4"
                rx="2"
                fill="currentColor"
                fill-opacity=".4"
            />
        </g>
        <g>
            <rect
                x="236"
                y="40"
                width="62"
                height="36"
                rx="8"
                fill="currentColor"
                fill-opacity=".14"
                stroke="currentColor"
                stroke-opacity=".35"
            />
            <rect
                x="245"
                y="50"
                width="34"
                height="4"
                rx="2"
                fill="currentColor"
                fill-opacity=".8"
            />
            <circle cx="249" cy="64" r="4" class="fill-comet" />
            <rect
                x="257"
                y="62"
                width="22"
                height="4"
                rx="2"
                fill="currentColor"
                fill-opacity=".4"
            />
        </g>
        <circle cx="262" cy="134" r="7" class="fill-comet" />
        <circle cx="96" cy="58" r="5" fill="currentColor" fill-opacity=".7" />
    </svg>
</template>
