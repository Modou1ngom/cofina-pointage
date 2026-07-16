import { ref } from 'vue';

export interface DeviceCoordinates {
    latitude: number;
    longitude: number;
    accuracy_metres?: number;
}

/**
 * Position GPS de l’appareil (navigateur / WebView mobile).
 * Utilisé au pointage (scan QR) — pas pour saisir manuellement lat/lng.
 */
export function useDeviceGeolocation() {
    const loading = ref(false);
    const error = ref<string | null>(null);

    function capture(): Promise<DeviceCoordinates | null> {
        error.value = null;

        if (!navigator.geolocation) {
            error.value =
                'La géolocalisation n’est pas disponible. Activez la localisation sur l’appareil et autorisez le navigateur.';

            return Promise.resolve(null);
        }

        loading.value = true;

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    loading.value = false;
                    resolve({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy_metres: pos.coords.accuracy,
                    });
                },
                (err) => {
                    loading.value = false;
                    const code = err?.code;
                    if (code === 1) {
                        error.value =
                            'Autorisation refusée : activez la localisation pour ce site / cette application, puis réessayez.';
                    } else if (code === 2) {
                        error.value = 'Position indisponible. Vérifiez le GPS ou le réseau, puis réessayez.';
                    } else if (code === 3) {
                        error.value = 'Délai dépassé. Réessayez en vous plaçant à l’extérieur ou près d’une fenêtre.';
                    } else {
                        error.value = err?.message
                            ? `Impossible d’obtenir la position : ${err.message}`
                            : 'Impossible d’obtenir la position GPS de l’appareil.';
                    }
                    resolve(null);
                },
                { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 },
            );
        });
    }

    return { loading, error, capture };
}
