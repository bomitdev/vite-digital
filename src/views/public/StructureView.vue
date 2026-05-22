<template>
  <div class="structure-page">
    <div class="container-fluid py-5">

      <!-- Header -->
      <div class="text-center mb-5 animate-slide-down">
        <h2 class="fw-bold title-premium">
          โครงสร้างองค์กรโรงพยาบาลชานุมาน
        </h2>

        <div class="title-underline"></div>

        <p class="text-muted subtitle">
          Divisional Structure & Reporting Line
        </p>
      </div>

      <!-- Loading -->
      <div class="chart-wrapper" v-if="loading">
        <div class="loading-container">
          <div class="spinner-border text-primary"></div>
          <p class="mt-3 text-muted">
            กำลังโหลดข้อมูลองค์กร...
          </p>
        </div>
      </div>

      <!-- Org Chart -->
      <div
        class="chart-wrapper"
        v-else-if="orgData && orgData.head"
      >
        <div class="stepped-tree">

          <!-- DIRECTOR -->
          <div class="director-section">

            <div class="org-card director-card">
              <div class="avatar-wrapper large">
                <img
                  :src="orgData.head.image || directorFallback"
                  @error="setFallback"
                />
              </div>

              <div class="info">
                <h5 class="name">
                  {{ orgData.head.name }}
                </h5>

                <div class="role">
                  {{ orgData.head.role }}
                </div>
              </div>
            </div>

            <div class="node-label">
              ผู้อำนวยการโรงพยาบาลชานุมาน
            </div>

            <div class="main-vertical-line"></div>
          </div>

          <!-- Main Horizontal -->
          <div class="main-horizontal-line"></div>

          <!-- ZONES -->
          <div class="zones-container">

            <!-- Medical -->
            <div
              class="zone zone-medical"
              v-if="medicalGroup"
            >
              <div class="zone-line"></div>

              <div class="group-node">

                <div class="badge-label">
                  {{ medicalGroup.name }}
                </div>

                <div class="org-card group-card">

                  <div class="avatar-wrapper">
                    <img
                      :src="medicalGroup.head.image || headFallback"
                      @error="setFallback"
                    />
                  </div>

                  <div class="info">
                    <h6 class="name">
                      {{ medicalGroup.head.name }}
                    </h6>
                  </div>

                </div>

              </div>
            </div>

            <!-- Nursing -->
            <div
              class="zone zone-nursing"
              v-if="nursingGroup"
            >
              <div class="zone-line"></div>

              <div class="group-node">

                <div class="badge-label">
                  {{ nursingGroup.name }}
                </div>

                <div class="org-card group-card">

                  <div class="avatar-wrapper">
                    <img
                      :src="nursingGroup.head.image || headFallback"
                      @error="setFallback"
                    />
                  </div>

                  <div class="info">
                    <h6 class="name">
                      {{ nursingGroup.head.name }}
                    </h6>
                  </div>

                </div>

                <!-- Subs -->
                <div
                  class="subs-section"
                  v-if="nursingGroup.subs?.length"
                >

                  <div class="subs-vertical-line"></div>

                  <div class="subs-horizontal-line"></div>

                  <div class="subs-grid">

                    <div
                      class="sub-node"
                      v-for="(sub, index) in nursingGroup.subs"
                      :key="index"
                    >

                      <div class="sub-line"></div>

                      <div class="badge-label sub-badge">
                        {{ sub.name }}
                      </div>

                      <div class="org-card sub-card">

                        <div class="avatar-wrapper mini">
                          <img
                            :src="sub.head.image || headFallback"
                            @error="setFallback"
                          />
                        </div>

                        <div class="info">
                          <h6 class="name">
                            {{ sub.head.name }}
                          </h6>
                        </div>

                      </div>

                    </div>

                  </div>
                </div>
              </div>
            </div>

            <!-- SUPPORT -->
            <div class="zone zone-support">

              <div class="zone-line"></div>

              <div class="support-vertical-line"></div>

              <div class="support-rows">

                <div
                  class="support-row"
                  v-for="(row, rowIndex) in supportRows"
                  :key="rowIndex"
                >

                  <div class="support-horizontal-line"></div>

                  <div class="support-nodes">

                    <div
                      class="support-node"
                      v-for="(group, gIndex) in row"
                      :key="gIndex"
                    >

                      <div class="support-node-line"></div>

                      <div class="badge-label">
                        {{ group.name }}
                      </div>

                      <div class="org-card group-card mini">

                        <div class="avatar-wrapper mini">
                          <img
                            :src="group.head.image || headFallback"
                            @error="setFallback"
                          />
                        </div>

                        <div class="info">
                          <h6 class="name">
                            {{ group.head.name }}
                          </h6>
                        </div>

                      </div>

                    </div>

                  </div>

                </div>

              </div>

            </div>

          </div>
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else
        class="text-center py-5"
      >
        <h5 class="text-muted">
          ไม่พบข้อมูลโครงสร้างองค์กร
        </h5>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

import directorFallback from '../../assets/avatars/director.png'
import headFallback from '../../assets/avatars/head.png'

const route = useRoute()

const orgData = ref(null)
const loading = ref(true)

const medicalGroup = computed(() => {
  return orgData.value?.groups?.[0] || null
})

const nursingGroup = computed(() => {
  return orgData.value?.groups?.[1] || null
})

const supportRows = computed(() => {

  if (!orgData.value?.groups) return []

  const supportGroups = orgData.value.groups.slice(2)

  const rows = []

  for (let i = 0; i < supportGroups.length; i += 4) {
    rows.push(supportGroups.slice(i, i + 4))
  }

  return rows
})

const setFallback = (e) => {
  e.target.src = headFallback
}

const fetchOrgChart = async () => {

  loading.value = true

  try {

    const deptId = route.query.dept_id || ''

    const res = await axios.get(
      `/api-hosoffice/get_org_chart.php?dept_id=${deptId}`
    )

    if (res.data.status === 'success') {
      orgData.value = res.data.data
    }

  } catch (error) {

    console.error(error)

  } finally {

    loading.value = false
  }
}

onMounted(fetchOrgChart)

watch(
  () => route.query.dept_id,
  fetchOrgChart
)
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');

.structure-page {
  min-height: 100vh;
  background: #f8fafc;
  font-family: 'Kanit', sans-serif;
  overflow-x: auto;
}

/* HEADER */

.title-premium {
  font-size: 2.4rem;
  color: #0f172a;
}

.title-underline {
  width: 100px;
  height: 5px;
  background: linear-gradient(to right, #0284c7, #3b82f6);
  border-radius: 20px;
  margin: 15px auto;
}

.subtitle {
  font-size: 1rem;
}

/* LOADING */

.loading-container {
  padding: 80px 0;
  text-align: center;
}

/* TREE */

.stepped-tree {
  width: max-content;
  min-width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* DIRECTOR */

.director-section {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.main-vertical-line {
  width: 8px;
  height: 60px;
  background: #1e293b;
}

.main-horizontal-line {
  width: 90%;
  height: 8px;
  background: #1e293b;
  border-radius: 10px;
}

/* ZONES */

.zones-container {
  width: 100%;
  display: flex;
  justify-content: space-between;
  gap: 40px;
  padding: 0 20px;
}

.zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.zone-line {
  width: 8px;
  height: 60px;
  background: #1e293b;
}

.zone-medical {
  width: 18%;
}

.zone-nursing {
  width: 34%;
}

.zone-support {
  width: 48%;
  align-items: flex-start;
}

/* SUPPORT */

.support-vertical-line {
  position: absolute;
  left: 0;
  top: 0;
  width: 8px;
  height: 95%;
  background: #1e293b;
}

.support-rows {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 60px;
}

.support-row {
  position: relative;
  padding-left: 40px;
}

.support-horizontal-line {
  width: 100%;
  height: 8px;
  background: #1e293b;
  margin-top: 60px;
}

.support-horizontal-line::before {
  content: '';
  position: absolute;
  left: 0;
  top: 60px;
  width: 40px;
  height: 8px;
  background: #1e293b;
}

.support-nodes {
  display: flex;
  gap: 20px;
  margin-top: -4px;
}

.support-node {
  flex: 1;
  min-width: 160px;
  max-width: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.support-node-line {
  width: 6px;
  height: 40px;
  background: #1e293b;
}

/* SUBS */

.subs-section {
  width: 100%;
  margin-top: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.subs-vertical-line {
  width: 8px;
  height: 60px;
  background: #1e293b;
}

.subs-horizontal-line {
  width: 100%;
  height: 8px;
  background: #1e293b;
}

.subs-grid {
  width: 100%;
  display: flex;
  justify-content: space-between;
}

.sub-node {
  width: 14%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.sub-line {
  width: 6px;
  height: 40px;
  background: #1e293b;
}

/* CARDS */

.org-card {
  background: white;
  border-radius: 18px;
  padding: 14px;
  border: 1px solid #e2e8f0;
  box-shadow:
    0 10px 25px rgba(0,0,0,0.05),
    0 4px 10px rgba(0,0,0,0.03);

  display: flex;
  flex-direction: column;
  align-items: center;

  transition: 0.25s ease;
}

.org-card:hover {
  transform: translateY(-4px);
}

.director-card {
  background: linear-gradient(
    135deg,
    #2563eb,
    #1d4ed8
  );

  color: white;

  min-width: 240px;
}

.group-card {
  min-width: 180px;
}

.sub-card {
  min-width: 140px;
  padding: 10px;
}

.org-card.mini {
  min-width: 150px;
}

/* AVATAR */

.avatar-wrapper {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  overflow: hidden;
  margin-bottom: 10px;
  border: 3px solid #e2e8f0;
}

.avatar-wrapper.large {
  width: 90px;
  height: 90px;
}

.avatar-wrapper.mini {
  width: 50px;
  height: 50px;
}

.avatar-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* TEXT */

.info {
  text-align: center;
}

.name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #0f172a;
}

.director-card .name {
  color: white;
  font-size: 1.1rem;
}

.role {
  background: rgba(255,255,255,0.2);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  margin-top: 5px;
}

/* BADGES */

.badge-label {
  background: #facc15;
  color: #111827;
  padding: 6px 14px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 700;
  margin-bottom: 10px;
  text-align: center;
  border: 1px solid #eab308;
}

.sub-badge {
  min-height: 50px;
  display: flex;
  align-items: center;
}

.node-label {
  margin-top: 15px;
  background: #facc15;
  padding: 8px 20px;
  border-radius: 10px;
  font-weight: 700;
}

/* ANIMATION */

.animate-slide-down {
  animation: slideDown 0.8s ease;
}

@keyframes slideDown {

  from {
    opacity: 0;
    transform: translateY(-20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* RESPONSIVE */

@media (max-width: 1400px) {

  .zones-container {
    flex-direction: column;
    align-items: center;
  }

  .zone-medical,
  .zone-nursing,
  .zone-support {
    width: 100%;
  }

  .support-row {
    overflow-x: auto;
    width: 100%;
  }

  .support-nodes {
    width: max-content;
  }

  .subs-grid {
    width: max-content;
    gap: 20px;
  }

  .sub-node {
    min-width: 150px;
  }
}

@media (max-width: 768px) {

  .title-premium {
    font-size: 1.6rem;
  }

  .org-card {
    min-width: 150px;
  }

  .group-card {
    min-width: 160px;
  }

  .support-node {
    min-width: 150px;
  }
}
</style>