jQuery(document).ready(function($) {
    if (typeof SolarPowerData !== 'undefined') {
        // Funktion zur Formatierung von Leistungswerten
        function formatPower(value) {
            value = parseFloat(value);
            if (value >= 1000000) {
                return (value / 1000000).toFixed(2) + ' MW';
            } else if (value >= 1000) {
                return (value / 1000).toFixed(2) + ' kW';
            } else {
                return value + ' W';
            }
        }

        function formatPowerHour(value) {
            value = parseFloat(value);
            if (value >= 1000000) {
                return (value / 1000000).toFixed(2) + ' MWh';
            } else if (value >= 1000) {
                return (value / 1000).toFixed(2) + ' kWh';
            } else {
                return value + ' Wh';
            }
        }

        // Daten vorbereiten
        var datasets = [];
        var colors = {
            productionNow: 'red',
            productionWatt: 'green',
            soldWatt: 'blue'
        };

        if (SolarPowerData.showProductionNow) {
            datasets.push({
                label: 'Momentane Leistung',
                data: SolarPowerData.productionNow,
                borderColor: colors.productionNow,
                borderWidth: 1,
                pointRadius: 0,
                tension: 0.2
            });
        }

        if (SolarPowerData.showProductionWatt) {
            datasets.push({
                label: 'Produzierte Energie (Wh)',
                data: SolarPowerData.productionWatt,
                borderColor: colors.productionWatt,
                borderWidth: 1,
                pointRadius: 0,
                tension: 0.2
            });
        }

        if (SolarPowerData.showSoldWatt) {
            datasets.push({
                label: 'Eingespeiste Energie (Wh)',
                data: SolarPowerData.soldWatt,
                borderColor: colors.soldWatt,
                borderWidth: 1,
                pointRadius: 0,
                tension: 0.2
            });
        }

        // Diagramm erstellen
        var ctx = document.getElementById('solarpowerChart').getContext('2d');
        new Chart(ctx, {
            type: SolarPowerData.chartType,
            data: {
                labels: SolarPowerData.timestamps,
                datasets: datasets
            },
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            parser: 'yyyy-MM-dd HH:mm:ss',
                            tooltipFormat: 'dd.MM.yyyy HH:mm',
                            unit: 'day',
                            displayFormats: {
                                day: 'dd.MM.yyyy'
                            }
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10
                        },
                        title: {
                            display: true,
                            text: 'Datum'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (SolarPowerData.chartType === 'bar') {
                                    return formatPowerHour(value);
                                } else {
                                    return formatPower(value);
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Leistung / Energie'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                var value = context.parsed.y;
                                if (label.includes('Leistung')) {
                                    return label + ': ' + formatPower(value);
                                } else {
                                    return label + ': ' + formatPowerHour(value);
                                }
                            }
                        }
                    }
                }
            }
        });
    } else {
        console.error('SolarPowerData ist nicht definiert.');
    }
});
