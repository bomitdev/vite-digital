<template>
  <div class="structure-page">
    <div class="container-fluid py-5">

      <!-- Header -->
      <div class="text-center mb-5 animate-slide-down">
        <h2 class="fw-bold title-premium">
          โครงสร้างองค์กรโรงพยาบาลชานุมาน
        </h2>

        <div class="title-underline"></div>

        <p class="text-muted subtitle mb-3">
          Divisional Structure & Reporting Line
        </p>
        
        <button v-if="route.query.dept_id" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold" @click="goToDept(null)">
          <i class="bi bi-arrow-left me-2"></i> แสดงแผนผังทั้งหมด
        </button>
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
          <div class="main-horizontal-line" v-if="!singleGroup"></div>

          <!-- Drill Down Mode for Sub-Department -->
          <div class="drill-down-container" v-if="singleSub">
            <div class="zone-line"></div>
            
            <div class="group-node drill-down-node">
              <div class="badge-label" style="font-size: 1.1rem; padding: 10px 30px; background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                {{ singleSub.name }}
              </div>
              
              <div class="org-card group-card" style="min-width: 220px;">
                <div class="avatar-wrapper large" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                  <img :src="singleSub.head.image || headFallback" @error="setFallback" />
                </div>
                <div class="info">
                  <h5 class="name">{{ singleSub.head.name }}</h5>
                  <div class="role text-muted small">หัวหน้าหน่วยงาน</div>
                </div>
              </div>

              <!-- Sub Staff -->
              <div class="staff-section" v-if="singleSub.staff?.length">
                <div class="subs-vertical-line"></div>
                <div class="badge-label staff-badge">บุคลากรในหน่วยงาน</div>
                <div class="staff-wrap-container">
                  <div class="staff-node" v-for="(member, idx) in singleSub.staff" :key="'substaff-'+idx">
                    <div class="org-card staff-card">
                      <div class="avatar-wrapper micro">
                        <img :src="member.image || headFallback" @error="setFallback" />
                      </div>
                      <div class="info">
                        <h6 class="name">{{ member.name }}</h6>
                        <div class="role text-muted" style="font-size: 0.75rem;">{{ member.role }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Drill Down Mode for Main Group -->
          <div class="drill-down-container" v-else-if="singleGroup">
            <div class="zone-line"></div>
            
            <div class="group-node drill-down-node">
              <div class="badge-label" style="font-size: 1.1rem; padding: 10px 30px;">
                {{ singleGroup.name }}
              </div>
              
              <div class="org-card group-card" style="min-width: 220px;">
                <div class="avatar-wrapper large">
                  <img :src="singleGroup.head.image || headFallback" @error="setFallback" />
                </div>
                <div class="info">
                  <h5 class="name">{{ singleGroup.head.name }}</h5>
                </div>
              </div>

              <!-- Group Staff -->
              <div class="staff-section" v-if="singleGroup.staff?.length">
                <div class="subs-vertical-line"></div>
                <div class="badge-label staff-badge">บุคลากรในกลุ่มงาน</div>
                <div class="staff-wrap-container">
                  <div class="staff-node" v-for="(member, idx) in singleGroup.staff" :key="'gstaff-'+idx">
                    <div class="org-card staff-card">
                      <div class="avatar-wrapper micro">
                        <img :src="member.image || headFallback" @error="setFallback" />
                      </div>
                      <div class="info">
                        <h6 class="name">{{ member.name }}</h6>
                        <div class="role text-muted" style="font-size: 0.75rem;">{{ member.role }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Group Subs -->
              <div class="subs-section" v-if="singleGroup.subs?.length">
                <div class="subs-vertical-line"></div>
                <div class="badge-label support-main-badge">หน่วยงานย่อย</div>
                <div class="subs-wrap-container" style="max-width: 1200px;">
                  <div class="sub-node-wrapped" v-for="(sub, idx) in singleGroup.subs" :key="'gsub-'+idx">
                    <div class="badge-label sub-badge">{{ sub.name }}</div>
                    <div class="org-card sub-card">
                      <div class="avatar-wrapper mini clickable" @click="goToSubDept(singleGroup.id, sub.id)" title="คลิกเพื่อดูเฉพาะหน่วยงานนี้">
                        <img :src="sub.head.image || headFallback" @error="setFallback" />
                      </div>
                      <div class="info">
                        <h6 class="name">{{ sub.head.name }}</h6>
                      </div>
                    </div>

                    <!-- Sub Staff -->
                    <div class="staff-wrap-container mt-3" v-if="sub.staff?.length" style="padding: 15px; background: transparent; border: none; box-shadow: none;">
                      <div class="staff-node" v-for="(s_member, s_idx) in sub.staff" :key="'sstaff-'+s_idx">
                        <div class="org-card staff-card micro-card">
                          <div class="avatar-wrapper micro">
                            <img :src="s_member.image || headFallback" @error="setFallback" />
                          </div>
                          <div class="info">
                            <h6 class="name" style="font-size: 0.8rem;">{{ s_member.name }}</h6>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- ZONES -->
          <div class="zones-container" v-else>

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

                  <div class="avatar-wrapper clickable" @click="goToDept(medicalGroup.id)" title="คลิกเพื่อดูเฉพาะกลุ่มงานนี้">
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

                  <div class="avatar-wrapper clickable" @click="goToDept(nursingGroup.id)" title="คลิกเพื่อดูเฉพาะกลุ่มงานนี้">
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
                <div class="subs-section" v-if="nursingGroup.subs?.length">
                  <div class="subs-vertical-line"></div>
                  <div class="subs-wrap-container">
                    <div class="sub-node-wrapped" v-for="(sub, index) in nursingGroup.subs" :key="index">
                      <div class="badge-label sub-badge">{{ sub.name }}</div>
                      <div class="org-card sub-card">
                        <div class="avatar-wrapper mini clickable" @click="goToSubDept(nursingGroup.id, sub.id)" title="คลิกเพื่อดูเฉพาะหน่วยงานนี้">
                          <img :src="sub.head.image || headFallback" @error="setFallback" />
                        </div>
                        <div class="info">
                          <h6 class="name">{{ sub.head.name }}</h6>
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
              <div class="badge-label support-main-badge">กลุ่มงานสนับสนุน / อื่นๆ</div>
              
              <div class="support-wrap-container">
                <div class="support-node-wrapped" v-for="(group, gIndex) in supportGroupsArray" :key="gIndex">
                  <div class="badge-label">{{ group.name }}</div>
                  <div class="org-card group-card mini">
                    <div class="avatar-wrapper mini clickable" @click="goToDept(group.id)" title="คลิกเพื่อดูเฉพาะกลุ่มงานนี้">
                      <img :src="group.head.image || headFallback" @error="setFallback" />
                    </div>
                    <div class="info">
                      <h6 class="name">{{ group.head.name }}</h6>
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
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import directorFallback from '../../assets/avatars/director.png'
import headFallback from '../../assets/avatars/head.png'

const route = useRoute()
const router = useRouter()

const orgData = ref(null)
const loading = ref(true)

const singleGroup = computed(() => {
  if (route.query.dept_id && orgData.value?.groups?.length === 1) {
    return orgData.value.groups[0]
  }
  return null
})

const singleSub = computed(() => {
  if (route.query.sub_id && singleGroup.value) {
    return singleGroup.value.subs?.find(s => s.id == route.query.sub_id) || null
  }
  return null
})

const medicalGroup = computed(() => {
  if (!orgData.value?.groups) return null
  return orgData.value.groups.find(g => g.name.includes('แพทย์')) || orgData.value.groups[0]
})

const nursingGroup = computed(() => {
  if (!orgData.value?.groups) return null
  return orgData.value.groups.find(g => g.name.includes('พยาบาล')) || orgData.value.groups[1]
})

const supportGroupsArray = computed(() => {
  if (!orgData.value?.groups) return []

  const med = medicalGroup.value
  const nurs = nursingGroup.value

  return orgData.value.groups.filter(g => g !== med && g !== nurs)
})

const setFallback = (e) => {
  e.target.src = headFallback
}

const goToDept = (deptId) => {
  if (deptId) {
    router.push({ query: { dept_id: deptId } })
  } else {
    router.push({ query: {} })
  }
}

const goToSubDept = (groupId, subId) => {
  if (groupId && subId) {
    router.push({ query: { dept_id: groupId, sub_id: subId } })
  }
}

const fetchOrgChart = async () => {

  loading.value = true

  try {

    const deptId = route.query.dept_id || ''

    const res = await axios.get(
      `/api-hosoffice/get_org_chart.php?dept_id=${deptId}`
    )

    if (res.data.status === 'success') {
      const data = res.data.data
      
      // Filter out duplicate sub-nodes that have the exact same name as the parent group, but merge their staff first!
      if (data.groups && Array.isArray(data.groups)) {
        data.groups.forEach(group => {
          if (!group.staff) group.staff = []

          if (group.subs && Array.isArray(group.subs)) {
            // 1. Identify duplicate subs
            const duplicateSubs = group.subs.filter(sub => sub.name === group.name)
            
            // 2. Merge their staff into the main group
            duplicateSubs.forEach(dupSub => {
              if (dupSub.staff && Array.isArray(dupSub.staff)) {
                group.staff = [...group.staff, ...dupSub.staff]
              }
            })

            // 3. Remove duplicate subs
            group.subs = group.subs.filter(sub => sub.name !== group.name)
          }

          // 4. Ensure the group head is not in the staff list
          if (group.head && group.head.name) {
            group.staff = group.staff.filter(member => member.name !== group.head.name)
          }
        })
      }
      
      orgData.value = data
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
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Kanit', sans-serif;
  overflow-x: auto;
  position: relative;
}

.structure-page::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 400px;
  background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent 50%),
              radial-gradient(circle at top left, rgba(139, 92, 246, 0.1), transparent 50%);
  pointer-events: none;
}

/* HEADER */
.title-premium {
  font-size: 2.8rem;
  background: linear-gradient(90deg, #1e3a8a, #3b82f6, #8b5cf6);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  text-shadow: 0 10px 20px rgba(59, 130, 246, 0.1);
  letter-spacing: -0.5px;
}

.title-underline {
  width: 120px;
  height: 6px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
  border-radius: 20px;
  margin: 20px auto;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
}

.subtitle {
  font-size: 1.1rem;
  color: #64748b;
  font-weight: 500;
  letter-spacing: 1px;
}

/* LOADING */
.loading-container {
  padding: 100px 0;
  text-align: center;
}

/* TREE */
.stepped-tree {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
}

/* DIRECTOR */
.director-section {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.main-vertical-line {
  width: 4px;
  height: 50px;
  background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
  border-radius: 4px;
}

.main-horizontal-line {
  width: 80%;
  height: 4px;
  background: #94a3b8;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* ZONES */
.zones-container {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: flex-start;
  gap: 30px;
  padding: 0 10px;
}

.zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
  min-width: 280px;
}

.zone-line {
  width: 4px;
  height: 50px;
  background: linear-gradient(to bottom, #94a3b8, #cbd5e1);
  border-radius: 4px;
}

/* CONTAINERS FOR SUBS & SUPPORT */
.subs-wrap-container, .support-wrap-container, .staff-wrap-container {
  width: 100%;
  max-width: 900px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 20px;
  background: rgba(255, 255, 255, 0.4);
  border: 2px dashed #cbd5e1;
  border-radius: 20px;
  padding: 25px;
  position: relative;
  box-shadow: inset 0 4px 15px rgba(0,0,0,0.02);
}

.staff-wrap-container {
  max-width: 1000px;
  gap: 15px;
}

.support-main-badge {
  background: linear-gradient(135deg, #475569, #334155);
  margin-bottom: 20px;
  font-size: 0.9rem;
  padding: 10px 24px;
}

.staff-badge {
  background: linear-gradient(135deg, #059669, #10b981);
  margin-bottom: 20px;
}

.sub-node-wrapped, .support-node-wrapped {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  min-width: 160px;
  max-width: 250px;
}

.staff-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 140px;
}

.subs-section {
  width: 100%;
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.subs-vertical-line {
  width: 4px;
  height: 40px;
  background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
  border-radius: 4px;
}

/* CARDS */
.org-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow: 0 10px 30px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.03);
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.org-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 20px 40px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.05);
  border-color: rgba(59, 130, 246, 0.3);
  background: rgba(255, 255, 255, 0.95);
}

.director-card {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: white;
  min-width: 260px;
  border: none;
  box-shadow: 0 15px 35px rgba(37, 99, 235, 0.3);
}

.director-card:hover {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
  box-shadow: 0 20px 45px rgba(37, 99, 235, 0.4);
}

.group-card { min-width: 180px; }
.sub-card { min-width: 140px; padding: 12px; }
.org-card.mini { min-width: 150px; }

.staff-card { min-width: 130px; padding: 10px; }
.micro-card { min-width: 110px; padding: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }

/* AVATAR */
.avatar-wrapper {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  overflow: hidden;
  margin-bottom: 12px;
  padding: 3px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}

.org-card:hover .avatar-wrapper {
  transform: rotate(5deg) scale(1.05);
}

.avatar-wrapper.large {
  width: 96px;
  height: 96px;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

.avatar-wrapper.mini {
  width: 56px;
  height: 56px;
}

.avatar-wrapper.micro {
  width: 46px;
  height: 46px;
  background: linear-gradient(135deg, #10b981, #34d399);
  margin-bottom: 8px;
}

.avatar-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid white;
}

.avatar-wrapper.clickable {
  cursor: pointer;
}
.avatar-wrapper.clickable:hover {
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

/* TEXT */
.info { text-align: center; }

.name {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.director-card .name {
  color: white;
  font-size: 1.2rem;
  text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.role {
  background: rgba(255,255,255,0.2);
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  margin-top: 6px;
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255,255,255,0.1);
}

/* BADGES */
.badge-label {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  padding: 8px 18px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 12px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
  letter-spacing: 0.5px;
  z-index: 2;
}

.sub-badge {
  min-height: 54px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #8b5cf6, #6d28d9);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
}

.node-label {
  margin-top: -10px;
  margin-bottom: 15px;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: #78350f;
  padding: 8px 24px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9rem;
  box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
  z-index: 2;
  position: relative;
}

/* ANIMATION */
.animate-slide-down {
  animation: slideDown 1s cubic-bezier(0.2, 0.8, 0.2, 1);
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* STAGGERED ENTRANCE FOR CARDS */
.stepped-tree .org-card {
  animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

.director-section .org-card { animation-delay: 0.1s; }
.zone .group-card { animation-delay: 0.2s; }
.support-wrap-container .group-card, .subs-wrap-container .sub-card { animation-delay: 0.4s; }
.drill-down-node > .org-card { animation-delay: 0.2s; }
.staff-card { animation-delay: 0.4s; }

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.drill-down-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* RESPONSIVE */
@media (max-width: 992px) {
  .main-horizontal-line, .zone-line, .subs-vertical-line {
    display: none; /* Hide rigid lines on smaller screens where elements stack */
  }
  .stepped-tree { gap: 30px; }
}

@media (max-width: 768px) {
  .title-premium { font-size: 1.8rem; }
  .org-card { min-width: 150px; }
  .group-card { min-width: 150px; }
  .sub-node-wrapped, .support-node-wrapped { min-width: 140px; }
  .subs-wrap-container, .support-wrap-container { padding: 15px; gap: 15px; }
}
</style>