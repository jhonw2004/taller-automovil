import 'leaflet/dist/leaflet.css';
import Alpine from 'alpinejs';

import { registerSearchStore } from './alpine/store';
import map, { singleMap } from './alpine/components/map';
import searchForm from './alpine/components/search';
import starRating from './alpine/components/rating';

registerSearchStore(Alpine);
Alpine.data('map', map);
Alpine.data('singleMap', singleMap);
Alpine.data('searchForm', searchForm);
Alpine.data('starRating', starRating);

window.Alpine = Alpine;
Alpine.start();
