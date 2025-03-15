const tf = require('@tensorflow/tfjs-node');
const axios = require('axios');

// Fetch data from PHP backend
async function fetchData() {
    try {
        const response = await axios.get('http://localhost/HR2/employee_db/supervisor/ml_data.php');
        return response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
        return [];
    }
}

// Normalize data
function normalizeData(data) {
    const leaveDays = data.map(row => row.total_leave_days);
    const performanceScores = data.map(row => row.performance_score);
    const attendanceRates = data.map(row => row.attendance_rate);

    const normalize = (arr) => {
        const mean = arr.reduce((a, b) => a + b, 0) / arr.length;
        const std = Math.sqrt(arr.reduce((a, b) => a + (b - mean) ** 2, 0) / arr.length);
        return arr.map(x => (x - mean) / std);
    };

    return {
        leaveDays: normalize(leaveDays),
        performanceScores: normalize(performanceScores),
        attendanceRates: normalize(attendanceRates),
    };
}

// Train autoencoder model
async function trainModel(data) {
    const tensorData = tf.tensor2d([
        data.leaveDays,
        data.performanceScores,
        data.attendanceRates,
    ]).transpose();

    const model = tf.sequential();
    model.add(tf.layers.dense({ units: 8, activation: 'relu', inputShape: [3] }));
    model.add(tf.layers.dense({ units: 2, activation: 'relu' })); // Encoder
    model.add(tf.layers.dense({ units: 8, activation: 'relu' })); // Decoder
    model.add(tf.layers.dense({ units: 3, activation: 'sigmoid' })); // Output

    model.compile({ optimizer: 'adam', loss: 'meanSquaredError' });

    await model.fit(tensorData, tensorData, {
        epochs: 50,
        batchSize: 32,
    });

    return model;
}

// Detect anomalies
async function detectAnomalies(model, data) {
    const tensorData = tf.tensor2d([
        data.leaveDays,
        data.performanceScores,
        data.attendanceRates,
    ]).transpose();

    const reconstructed = model.predict(tensorData);
    const error = tf.mean(tf.square(tf.sub(tensorData, reconstructed)), 1);
    const errorThreshold = tf.mean(error).dataSync()[0] + 2 * tf.variance(error).dataSync()[0];

    return error.greater(errorThreshold).dataSync();
}

// Main function
async function main() {
    const rawData = await fetchData();
    if (rawData.length === 0) {
        console.error('No data fetched from the API.');
        return;
    }

    const normalizedData = normalizeData(rawData);
    const model = await trainModel(normalizedData);
    const anomalies = await detectAnomalies(model, normalizedData);

    console.log('Anomalies:', anomalies);
}

main();