const API_URL = 'api_proxy.php?feed=';

async function getFeedData(feedName) {
    const response = await fetch(`${API_URL}${feedName}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
            // API key is handled by the proxy now
        }
    });
    if (!response.ok) {
        throw new Error('Failed to fetch feed data');
    }
    return await response.json();
}

async function sendFeedData(feedName, value) {
    const response = await fetch(`${API_URL}${feedName}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
            // API key is handled by the proxy now
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