let currentValue = 0;

function animateNumber() {
    const intervalId = setInterval(() => {
        // Increment the current value
        currentValue++;

        // Check if the target value is reached
        if (currentValue >= targetValue) {
            clearInterval(intervalId); // Stop the interval
            document.getElementById('number-display').textContent = targetValue; // Set the final value
            return;
        }

        // Update the displayed value
        document.getElementById('number-display').textContent = currentValue;
    }, 100); // Interval in milliseconds (10 = 10 milliseconds)
}

function animateNumbeIVA() {
    const intervalId = setInterval(() => {
        // Increment the current value
        currentValue++;

        // Check if the target value is reached
        if (currentValue >= targetValue) {
            clearInterval(intervalId); // Stop the interval
            document.getElementById('number-iva').textContent = targetValueIVA; // Set the final value
            return;
        }

        // Update the displayed value
        document.getElementById('number-iva').textContent = currentValue;
    }, 100); // Interval in milliseconds (10 = 10 milliseconds)
}
