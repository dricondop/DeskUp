import './bootstrap';
import { createApp } from 'vue';

// Import components
import HeightDetection from './components/HeightDetection.vue';

const app = createApp({});

// Register components
app.component('HeightDetection', HeightDetection);

app.mount('#height-detection-app');