const API_URL = 'https://io.adafruit.com/api/v2/nhanphan2002/feeds/';
const API_KEY = 'aio_PknY103kEThd6vvHwSh8n97ah0zh';

async function getFeedData(feedName) {
    const response = await fetch(`${API_URL}${feedName}/data`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-AIO-Key': API_KEY
        }
    });
    if (!response.ok) {
        throw new Error('Failed to fetch feed data');
    }
    return await response.json();
}

async function sendFeedData(feedName, value) {
    const response = await fetch(`${API_URL}${feedName}/data`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-AIO-Key': API_KEY
        },
        body: JSON.stringify({ value: value })
    });
    if (!response.ok) {
        throw new Error('Failed to send feed data');
    }
    return await response.json();
}

async function getDeviceStatus(deviceId) {
    return await getFeedData(`device-status-${deviceId}`);
}

async function controlDevice(deviceId, action) {
    return await sendFeedData(`device-control-${deviceId}`, action);
}

async function getSensorData(sensorId) {
    return await getFeedData(`sensor-data-${sensorId}`);
}