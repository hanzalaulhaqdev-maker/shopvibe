document.addEventListener('DOMContentLoaded', function() {
    var images = ['hero1.png', 'hero2.png', 'hero3.png', 'hero4.png'];
    var layerA = document.getElementById('hero-a');
    var layerB = document.getElementById('hero-b');
    var activeLayer = 'a';
    var index = 0;

    function transition() {
        var nextIndex = (index + 1) % images.length;
        var inactiveLayer = activeLayer === 'a' ? layerB : layerA;
        var currentLayer = activeLayer === 'a' ? layerA : layerB;

        // Set new image on inactive layer
        inactiveLayer.style.backgroundImage = "url('assets/images/" + images[nextIndex] + "')";

        // Reset animation
        inactiveLayer.style.animation = 'none';
        inactiveLayer.offsetHeight; // Trigger reflow
        inactiveLayer.style.animation = 'kenburns 7s ease-in-out infinite alternate';

        // Crossfade
        currentLayer.style.opacity = '0';
        inactiveLayer.style.opacity = '1';

        // Swap
        activeLayer = activeLayer === 'a' ? 'b' : 'a';
        index = nextIndex;
    }

    if (layerA && layerB) {
        setInterval(transition, 5000);
    }
});