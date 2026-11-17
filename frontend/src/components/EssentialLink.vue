<template>
  <!-- Internal application links -->
  <q-item
    v-if="link.startsWith('/')"
    clickable
    :to="link"
    class="essential-link"
  >
    <q-item-section v-if="icon" avatar>
      <q-icon :name="icon" />
    </q-item-section>

    <q-item-section>
      <q-item-label class="essential-link-title">
        {{ label }}
      </q-item-label>

      <q-item-label caption class="essential-link-caption">
        {{ caption }}
      </q-item-label>
    </q-item-section>
  </q-item>

  <!-- External website links -->
  <q-item
    v-else
    clickable
    tag="a"
    target="_blank"
    :href="link"
    class="essential-link"
  >
    <q-item-section v-if="icon" avatar>
      <q-icon :name="icon" />
    </q-item-section>

    <q-item-section>
      <q-item-label class="essential-link-title">
        {{ label }}
      </q-item-label>

      <q-item-label caption class="essential-link-caption">
        {{ caption }}
      </q-item-label>
    </q-item-section>
  </q-item>
</template>

<script setup lang="ts">
export interface EssentialLinkProps {
  label: string;
  caption?: string;
  link?: string;
  icon?: string;
}

withDefaults(defineProps<EssentialLinkProps>(), {
  caption: "",
  link: "#",
  icon: ""
});
</script>

<style scoped>
.essential-link-title {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.4;
}

.essential-link-caption {
  font-size: 12px;
  line-height: 1.4;
}
</style>
