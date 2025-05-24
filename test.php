<script>
const presets = [
    { top: '1rem', left: '1rem', rotate: -10 },
    { top: '0.75rem', left: '50%', transform: 'translateX(-50%)', rotate: 8 },
    { top: '1rem', right: '1rem', rotate: 10 },
    { bottom: '1rem', left: '1rem', rotate: 12 },
    { bottom: '0.75rem', left: '50%', transform: 'translateX(-50%)', rotate: -8 },
    { bottom: '1rem', right: '1rem', rotate: -12 },
];

// Zones fixes non superposées (en % de la taille du carré)
const zones = [
    { top: '10%', left: '10%' },
    { top: '10%', left: '40%' },
    { top: '10%', left: '70%' },
    { top: '70%', left: '10%' },
    { top: '70%', left: '40%' },
    { top: '70%', left: '70%' }
];

function shuffle(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

function resetCards() {
    for (let i = 0; i < 6; i++) {
        const card = document.getElementById(`card${i}`);
        card.style.top = '';
        card.style.bottom = '';
        card.style.left = '';
        card.style.right = '';
        card.style.transform = '';
        card.style.rotate = '';

        const preset = presets[i];
        Object.keys(preset).forEach(k => {
            if (k === 'rotate') {
                card.style.rotate = `${preset[k]}deg`;
            } else {
                card.style[k] = preset[k];
            }
        });
    }
}

function randomizeCards() {
    const shuffledZones = shuffle([...zones]);

    for (let i = 0; i < 6; i++) {
        const card = document.getElementById(`card${i}`);
        const zone = shuffledZones[i];
        card.style.top = zone.top;
        card.style.left = zone.left;
        card.style.bottom = '';
        card.style.right = '';
        card.style.transform = 'translate(-50%, -50%)';
        card.style.rotate = `${Math.floor(Math.random() * 30 - 15)}deg`;
    }
}

// Initialiser en disposition normale
resetCards();
</script>