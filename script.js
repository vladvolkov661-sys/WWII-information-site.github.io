// ===== LOADER =====
window.addEventListener('load', function() {
    const loader = document.getElementById('loader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.remove(), 300);
    }
    initScrollAnimations();
});

// ===== SCROLL ANIMATIONS =====
function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    document.querySelectorAll('.animate-on-scroll, .animate-left, .animate-right').forEach(el => {
        observer.observe(el);
    });
}

// ===== MAP =====
let map;
let markers = [];
let frontlineLayers = [];

const frontlineCoordinates = {
    1941: {
        start: [[59.50, 28.00], [58.50, 27.50], [57.00, 26.50], [55.50, 25.50], [54.00, 25.00], [52.00, 24.00], [50.00, 25.00], [48.00, 26.00], [46.50, 28.00], [45.00, 30.00]],
        end: [[59.93, 30.32], [58.50, 31.00], [57.00, 32.00], [55.75, 37.62], [54.50, 36.00], [52.00, 35.00], [50.00, 34.00], [48.00, 35.00], [46.50, 36.00], [45.00, 37.00]]
    },
    1942: {
        start: [[59.93, 30.32], [58.00, 31.50], [56.50, 33.00], [55.00, 35.00], [53.00, 36.00], [51.00, 38.00], [49.00, 40.00], [47.50, 41.00], [46.00, 42.00], [45.00, 43.00]],
        end: [[59.93, 30.32], [57.50, 32.00], [55.50, 34.00], [53.50, 38.00], [51.50, 42.00], [49.50, 44.00], [48.70, 44.51], [47.00, 43.00], [45.50, 42.50], [44.50, 41.00]]
    },
    1943: {
        start: [[59.93, 30.32], [57.00, 31.50], [55.00, 33.00], [53.00, 35.00], [51.00, 37.00], [49.00, 40.00], [47.50, 42.00], [46.00, 43.00], [44.50, 42.00]],
        end: [[59.93, 30.32], [56.50, 31.00], [54.50, 32.00], [52.50, 34.00], [50.45, 30.52], [48.50, 32.00], [47.00, 35.00], [45.50, 37.00], [44.50, 39.00]]
    },
    1944: {
        start: [[59.93, 30.32], [56.00, 30.50], [54.00, 31.50], [52.00, 32.50], [50.00, 33.50], [48.00, 34.50], [46.50, 36.00], [45.00, 38.00], [44.00, 40.00]],
        end: [[59.50, 27.00], [57.00, 26.50], [55.00, 27.00], [53.00, 28.00], [51.00, 29.00], [49.00, 30.00], [47.00, 31.00], [45.50, 33.00], [44.50, 35.00]]
    },
    1945: {
        start: [[56.00, 24.00], [54.50, 23.50], [53.00, 24.00], [51.50, 25.00], [50.00, 26.00], [48.50, 27.00], [47.00, 28.00], [45.50, 29.00], [44.50, 30.00]],
        end: [[54.52, 18.50], [53.50, 19.50], [52.52, 20.50], [51.50, 21.50], [50.50, 22.50], [49.50, 23.50], [48.50, 24.50], [47.50, 25.50], [46.50, 26.50], [45.50, 27.50]]
    }
};

function initMap() {
    map = L.map('map', {
        center: [52.0, 35.0],
        zoom: 5,
        zoomControl: true,
        scrollWheelZoom: true,
        attributionControl: false
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '',
        maxZoom: 10
    }).addTo(map);

    drawFrontline(currentYear);
    drawMarkers(eventsData);

    // ✅ === ЛЕГЕНДА — ДОБАВЛЕНА СЮДА ===
    var legend = L.control({ position: 'bottomright' });

    legend.onAdd = function(map) {
        var div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = `
            <h4>Условные обозначения</h4>
            <div class="map-legend-item">
                <div class="legend-line legend-start"></div>
                <span>Линия на 1 января</span>
            </div>
            <div class="map-legend-item">
                <div class="legend-line legend-end"></div>
                <span>Линия на 31 декабря</span>
            </div>
            <div class="map-legend-item">
                <div class="legend-zone"></div>
                <span>Зона боевых действий</span>
            </div>
        `;
        return div;
    };

    legend.addTo(map);
    // ✅ === КОНЕЦ ЛЕГЕНДЫ ===
}

function drawFrontline(year) {
    frontlineLayers.forEach(layer => {
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });
    frontlineLayers = [];

    const data = frontlineCoordinates[year];
    if (!data || !data.start || !data.end) return;

    const zoneCoords = [...data.start, ...data.end.slice().reverse()];
    
    const zone = L.polygon(zoneCoords, {
        className: 'frontline-zone',
        fillOpacity: 0.01,
        stroke: true,
        color: '#8b0000',
        weight: 1,
        opacity: 0.5
    }).addTo(map);
    frontlineLayers.push(zone);

    const startLine = L.polyline(data.start, {
        className: 'frontline-start',
        color: '#ff4444',
        weight: 3,
        dashArray: '8, 6',
        opacity: 0.95
    }).addTo(map);
    frontlineLayers.push(startLine);

    const endLine = L.polyline(data.end, {
        className: 'frontline-end',
        color: '#c4a35a',
        weight: 3,
        dashArray: '8, 6',
        opacity: 0.95
    }).addTo(map);
    frontlineLayers.push(endLine);
}

function drawMarkers(events) {
    markers.forEach(m => {
        if (map.hasLayer(m)) {
            map.removeLayer(m);
        }
    });
    markers = [];

    events.forEach(event => {
        if (!event.latitude || !event.longitude) return;

        const marker = L.circleMarker([event.latitude, event.longitude], {
            radius: 10,
            fillColor: '#8b0000',
            color: '#c4a35a',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        }).addTo(map);

        const tooltipContent = `
            <div style="font-family: Georgia, serif;">
                <div style="color: #b22222; font-size: 11px; margin-bottom: 3px;">
                    📅 ${event.date_start ? new Date(event.date_start).toLocaleDateString('ru-RU') : event.year}
                </div>
                <div style="color: #c4a35a; font-size: 13px; font-weight: bold; margin-bottom: 5px;">
                    ${event.title}
                </div>
                <div style="color: #a0a0a0; font-size: 11px; line-height: 1.4;">
                    ${(event.description || '').substring(0, 100)}...
                </div>
            </div>
        `;

        marker.bindTooltip(tooltipContent, {
            sticky: true,
            direction: 'top',
            offset: [0, -10],
            className: 'leaflet-custom-tooltip'
        });

        marker.on('click', function() {
            window.location.href = `event.php?id=${event.id}`;
        });

        marker.on('mouseover', function() {
            this.setStyle({ radius: 14, fillOpacity: 1 });
        });
        marker.on('mouseout', function() {
            this.setStyle({ radius: 10, fillOpacity: 0.8 });
        });

        markers.push(marker);
    });
}

function changeYear(year) {
    window.location.href = `?year=${year}`;
}

function applyFilters() {
    const category = document.getElementById('filterCategory').value;
    const year = document.getElementById('filterYear').value;
    const search = document.getElementById('filterSearch').value.toLowerCase();

    document.querySelectorAll('#eventsGrid .card').forEach(card => {
        const cardCategory = card.dataset.category || '';
        const cardYear = card.dataset.year || '';
        const cardTitle = card.querySelector('h3').textContent.toLowerCase();

        let show = true;
        if (category && cardCategory !== category) show = false;
        if (year && cardYear !== year) show = false;
        if (search && !cardTitle.includes(search)) show = false;

        card.style.display = show ? 'block' : 'none';
        if (show) {
            card.style.animation = 'fadeInUp 0.5s ease both';
        }
    });
}

function filterHeroes() {
    const search = document.getElementById('heroSearch').value.toLowerCase();
    document.querySelectorAll('#heroesGrid .card').forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        card.style.display = name.includes(search) ? 'block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('map')) {
        initMap();
    }
});