import { api } from "@/boot/axios";

interface PushSubscriptionResponse {
  publicKey: string;
}

function urlBase64ToUint8Array(base64String: string): ArrayBuffer {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");

  const rawData = window.atob(base64);
  const output = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    output[i] = rawData.charCodeAt(i);
  }

  return output.buffer as ArrayBuffer;
}

export async function registerServiceWorker(): Promise<ServiceWorkerRegistration> {
  if (!("serviceWorker" in navigator)) {
    throw new Error("Your browser does not support service workers.");
  }

  return navigator.serviceWorker.register("/sw.js");
}

export async function enablePushNotifications(): Promise<void> {
  if (!("Notification" in window) || !("PushManager" in window)) {
    throw new Error("Your browser does not support push notifications.");
  }

  const permission = await Notification.requestPermission();

  if (permission !== "granted") {
    throw new Error("Notification permission was not granted.");
  }

  const registration = await registerServiceWorker();

  const response = await api.get<PushSubscriptionResponse>(
    "/push/vapid-public-key"
  );

  const applicationServerKey = urlBase64ToUint8Array(response.data.publicKey);

  const existingSubscription = await registration.pushManager.getSubscription();

  const subscription =
    existingSubscription ||
    (await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey
    }));

  const subscriptionJson = subscription.toJSON();

  await api.post("/push/subscribe", {
    endpoint: subscription.endpoint,
    keys: subscriptionJson.keys,
    contentEncoding: "aes128gcm"
  });
}

export async function disablePushNotifications(): Promise<void> {
  const registration = await navigator.serviceWorker.getRegistration("/");

  if (!registration) {
    return;
  }

  const subscription = await registration.pushManager.getSubscription();

  if (!subscription) {
    return;
  }

  await api.delete("/push/unsubscribe", {
    data: {
      endpoint: subscription.endpoint
    }
  });

  await subscription.unsubscribe();
}
