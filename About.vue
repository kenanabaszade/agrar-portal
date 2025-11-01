<template>
    <div class="about-page">
      <!-- Hero Section -->
      <div class="hero-section">
        <img
          src="https://images.unsplash.com/photo-1516253593875-bd1e9833a440?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxhZ3JpY3VsdHVyYWwlMjBmaWVsZCUyMGxhbmRzY2FwZXxlbnwxfHx8fDE3NTg3OTkwNjZ8MA&ixlib=rb-4.1.0&q=80&w=1080"
          alt="Agricultural landscape"
          class="hero-bg"
        />
        <div class="hero-content">
          <div class="hero-icon">
            <SproutIcon />
          </div>
          <h1>Haqqımızda</h1>
          <p>
            Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyi - 
            kənd təsərrüfatı sahəsində təlim və məsləhətçilik xidmətlərinin aparıcı təşkilatı
          </p>
        </div>
      </div>
  
      <div class="main-container">
        <!-- Mission and Vision -->
        <section class="section">
          <div class="mission-vision-grid">
            <div class="card">
              <div class="card-header">
                <h3>
                  <TargetIcon />
                  Missiyamız
                </h3>
              </div>
              <div class="card-content">
                <p>
                  Azərbaycan kənd təsərrüfatının müasirləşdirilməsi və fermerlərinin biliklərinin artırılması məqsədilə 
                  keyfiyyətli təlim proqramları, praktiki məsləhətlər və innovativ həllər təqdim etmək. 
                  Ölkəmizdə qida təhlükəsizliyinin təmin edilməsi və kənd təsərrüfatı məhsuldarlığının artırılmasına töhfə vermək.
                </p>
              </div>
            </div>
  
            <div class="card">
              <div class="card-header">
                <h3>
                  <EyeIcon />
                  Vizyonumuz
                </h3>
              </div>
              <div class="card-content">
                <p>
                  Regionun aparıcı aqrar təlim və məsləhətçilik mərkəzi olmaq. Müasir texnologiyalar və ən yaxşı təcrübələr əsasında 
                  fermerlərə, aqronomlara və kənd təsərrüfatı şirkətlərinə dünyəvi standartlarda xidmətlər göstərmək. 
                  Davamlı və ekoloji təmiz kənd təsərrüfatının inkişafına rəhbərlik etmək.
                </p>
              </div>
            </div>
          </div>
        </section>
  
        <!-- Statistics -->
        <section class="section">
          <h2>Nailiyyətlərimiz Rəqəmlərlə</h2>
          <div class="stats-grid">
            <div v-for="(stat, index) in stats" :key="index" class="stat-card">
              <div class="stat-icon">
                <component :is="stat.icon" />
              </div>
              <div class="stat-value">{{ stat.value }}</div>
              <p>{{ stat.label }}</p>
            </div>
          </div>
        </section>
  
        <!-- Timeline -->
        <section class="section">
          <h2>Tarixi Yolumuz</h2>
          <div class="timeline">
            <div class="timeline-line"></div>
            <div
              v-for="(milestone, index) in milestones"
              :key="index"
              class="timeline-item"
            >
              <div class="timeline-marker">
                <component :is="milestone.icon" />
              </div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span class="timeline-badge">{{ milestone.year }}</span>
                  <h3>{{ milestone.title }}</h3>
                </div>
                <p>{{ milestone.description }}</p>
              </div>
            </div>
          </div>
        </section>
  
        <!-- Team -->
        <section class="section">
          <h2>Komandamız</h2>
          <div class="team-grid">
            <div
              v-for="member in teamMembers"
              :key="member.id"
              class="team-card"
            >
              <div class="team-member-content">
                <div class="member-center">
                  <div class="avatar">{{ getInitials(member.name) }}</div>
                  <h3>{{ member.name }}</h3>
                  <p class="position">{{ member.position }}</p>
                  <span class="badge">{{ member.department }}</span>
                  <p class="experience">{{ member.experience }} təcrübə</p>
                </div>
                
                <div class="specialization">
                  <h4>İxtisas sahələri:</h4>
                  <div class="specialization-tags">
                    <span
                      v-for="(spec, index) in member.specialization"
                      :key="index"
                      class="tag"
                    >
                      {{ spec }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
  
        <!-- Values -->
        <section class="section">
          <h2>Dəyərlərimiz</h2>
          <div class="values-grid">
            <div class="value-card">
              <div class="value-icon value-icon-blue">
                <ShieldIcon />
              </div>
              <h3>Keyfiyyət</h3>
              <p>
                Bütün fəaliyyətlərimizdə ən yüksək keyfiyyət standartlarına əməl edirik və davamlı təkmilləşmə prinsipini həyata keçiririk.
              </p>
            </div>
  
            <div class="value-card">
              <div class="value-icon value-icon-green">
                <HeartIcon />
              </div>
              <h3>Fermerlərə Həssaslıq</h3>
              <p>
                Fermerlərinin ehtiyaclarını anlamaq və onlara ən yaxşı həlləri təqdim etmək bizim əsas prioritetimizdir.
              </p>
            </div>
  
            <div class="value-card">
              <div class="value-icon value-icon-purple">
                <TrendingUpIcon />
              </div>
              <h3>İnnovasiya</h3>
              <p>
                Ən müasir texnologiyalar və metodları tətbiq edərək kənd təsərrüfatının gələcəyini formalaşdırırıq.
              </p>
            </div>
          </div>
        </section>
  
        <!-- Contact CTA -->
        <section class="section">
          <div class="cta-card">
            <h3>Bizimlə əlaqə saxlayın</h3>
            <p>
              Hər hansı sualınız var? Komandamızla əlaqə saxlayın və kənd təsərrüfatı sahəsində 
              necə daha yaxşı xidmət göstərə biləcəyimizi öyrənin.
            </p>
            <div class="cta-buttons">
              <button class="btn-primary">
                <PhoneIcon />
                Bizimlə əlaqə saxlayın
              </button>
              <button class="btn-outline">
                <MailIcon />
                E-poçt göndərin
              </button>
            </div>
          </div>
        </section>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { markRaw } from 'vue'
  import {
    Users as UsersIcon,
    Target as TargetIcon,
    Award as AwardIcon,
    Calendar as CalendarIcon,
    Phone as PhoneIcon,
    Mail as MailIcon,
    Globe as GlobeIcon,
    Sprout as SproutIcon,
    BookOpen as BookOpenIcon,
    TrendingUp as TrendingUpIcon,
    Shield as ShieldIcon,
    Heart as HeartIcon,
    Star as StarIcon,
    Eye as EyeIcon
  } from 'lucide-vue-next'
  
  interface TeamMember {
    id: string
    name: string
    position: string
    department: string
    experience: string
    specialization: string[]
    avatar?: string
  }
  
  interface Milestone {
    year: string
    title: string
    description: string
    icon: any
  }
  
  const teamMembers: TeamMember[] = [
    {
      id: '1',
      name: 'Dr. Rəşad Məmmədov',
      position: 'İdarə Rəisi',
      department: 'Rəhbərlik',
      experience: '15+ il',
      specialization: ['Aqrar siyasət', 'Strateji planlaşdırma', 'Beynəlxalq əməkdaşlıq'],
      avatar: undefined
    },
    {
      id: '2',
      name: 'Prof. Leyla Əliyeva',
      position: 'Təlim Departamenti Müdiri',
      department: 'Təlim',
      experience: '12+ il',
      specialization: ['Bitki yetişdiriciliyi', 'Orqanik fermençilik', 'Təlim metodikası'],
      avatar: undefined
    },
    {
      id: '3',
      name: 'Dr. Mikayıl Həsənov',
      position: 'Tədqiqat Şöbəsi Müdiri',
      department: 'Tədqiqat',
      experience: '10+ il',
      specialization: ['Aqrar innovasiyalar', 'Su təsərrüfatı', 'İqlim dəyişikliyi'],
      avatar: undefined
    },
    {
      id: '4',
      name: 'Fatimə Quliyeva',
      position: 'Rəqəmsal Platformalar Mütəxəssisi',
      department: 'Texnologiya',
      experience: '8+ il',
      specialization: ['E-təlim', 'Rəqəmsal həllər', 'UI/UX dizayn'],
      avatar: undefined
    },
    {
      id: '5',
      name: 'Dr. Səidə Məmmədova',
      position: 'Fermerlə Əlaqələr Koordinatoru',
      department: 'Əlaqələr',
      experience: '7+ il',
      specialization: ['Fermer təşkilatları', 'Sahə məsləhətçiliyi', 'Kənd təsərrüfatı məhsulları'],
      avatar: undefined
    },
    {
      id: '6',
      name: 'Nigar Səfərova',
      position: 'Beynəlxalq Layihələr Mütəxəssisi',
      department: 'Beynəlxalq əlaqələr',
      experience: '6+ il',
      specialization: ['Layihə idarəetməsi', 'Beynəlxalq qrantlar', 'Monitorinq və qiymətləndirmə'],
      avatar: undefined
    }
  ]
  
  const milestones: Milestone[] = [
    {
      year: '2018',
      title: 'Agentliyin Təsisi',
      description: 'Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyinin yaradılması',
      icon: markRaw(SproutIcon)
    },
    {
      year: '2019',
      title: 'İlk Təlim Proqramları',
      description: 'Fermerlər üçün ilk peşəkar təlim proqramlarının başladılması və 500+ fermerin təlim alması',
      icon: markRaw(BookOpenIcon)
    },
    {
      year: '2020',
      title: 'Rəqəmsal Transformasiya',
      description: 'COVID-19 pandemiyası dövründə onlayn təlim platformasının yaradılması',
      icon: markRaw(TrendingUpIcon)
    },
    {
      year: '2021',
      title: 'Beynəlxalq Əməkdaşlıq',
      description: 'FAO və digər beynəlxalq təşkilatlarla əməkdaşlıq müqavilələrinin imzalanması',
      icon: markRaw(GlobeIcon)
    },
    {
      year: '2022',
      title: 'Sertifikatlaşdırma Sistemi',
      description: 'Rəsmi sertifikatlaşdırma sisteminin tətbiqi və 1000+ sertifikatın verilməsi',
      icon: markRaw(AwardIcon)
    },
    {
      year: '2023',
      title: 'Mobil Tətbiq',
      description: 'Fermerlər üçün mobil tətbiqin işə salınması və sahə məsləhətçiliyi xidmətinin genişləndirilməsi',
      icon: markRaw(PhoneIcon)
    },
    {
      year: '2024',
      title: 'İnnovasiya Mərkəzi',
      description: 'Aqrar innovasiyalar və tədqiqat mərkəzinin açılması',
      icon: markRaw(TargetIcon)
    },
    {
      year: '2025',
      title: 'Yeni Platform',
      description: 'Yenilənmiş təlim platformasının istifadəyə verilməsi və 5000+ aktiv istifadəçi',
      icon: markRaw(StarIcon)
    }
  ]
  
  const stats = [
    { label: 'Aktiv İstifadəçilər', value: '5,000+', icon: markRaw(UsersIcon) },
    { label: 'Verilmiş Təlimlər', value: '150+', icon: markRaw(BookOpenIcon) },
    { label: 'Qazanılan Sertifikatlar', value: '3,500+', icon: markRaw(AwardIcon) },
    { label: 'Fəaliyyət İli', value: '7', icon: markRaw(CalendarIcon) }
  ]
  
  const getInitials = (name: string): string => {
    return name.split(' ').map(n => n[0]).join('')
  }
  </script>
  
  <style scoped>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
  
  * {
    font-family: 'Poppins', sans-serif;
    box-sizing: border-box;
  }
  
  .about-page {
    min-height: 100vh;
    background-color: #BDE3DC;
  }
  
  .hero-section {
    position: relative;
    background: linear-gradient(to right, #155530, #124529);
    padding: 5rem 0;
  }
  
  .hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.2;
  }
  
  .hero-content {
    position: relative;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    text-align: center;
  }
  
  .hero-icon {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
    border-radius: 12px;
    background-color: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .hero-icon svg {
    width: 2.5rem;
    height: 2.5rem;
    color: white;
  }
  
  .hero-content h1 {
    font-size: 3rem;
    font-weight: 600;
    color: white;
    margin-bottom: 1.5rem;
  }
  
  .hero-content p {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    max-width: 48rem;
    margin: 0 auto;
    line-height: 1.7;
  }
  
  .main-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 4rem 1.5rem;
  }
  
  .section {
    margin-bottom: 4rem;
  }
  
  .section h2 {
    font-size: 1.875rem;
    font-weight: 600;
    color: #0d1f0d;
    margin-bottom: 3rem;
    text-align: center;
  }
  
  .mission-vision-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
  }
  
  @media (max-width: 1024px) {
    .mission-vision-grid {
      grid-template-columns: 1fr;
    }
  }
  
  .card {
    background: white;
    border: 1px solid rgba(26, 102, 56, 0.12);
    border-radius: 10px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }
  
  .card-header {
    border-bottom: 1px solid rgba(26, 102, 56, 0.12);
    padding: 1.5rem;
  }
  
  .card-header h3 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: #0d1f0d;
  }
  
  .card-header h3 svg {
    width: 1.5rem;
    height: 1.5rem;
    color: #1A6638;
  }
  
  .card-content {
    padding: 1.5rem;
  }
  
  .card-content p {
    color: #6b7c6b;
    line-height: 1.7;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
  }
  
  @media (max-width: 1024px) {
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 640px) {
    .stats-grid {
      grid-template-columns: 1fr;
    }
  }
  
  .stat-card {
    background: white;
    border: 1px solid rgba(26, 102, 56, 0.12);
    border-radius: 10px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    text-align: center;
  }
  
  .stat-icon {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 1rem;
    background-color: #dcf2e3;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .stat-icon svg {
    width: 1.5rem;
    height: 1.5rem;
    color: #1A6638;
  }
  
  .stat-value {
    font-size: 1.875rem;
    font-weight: 600;
    color: #1A6638;
    margin-bottom: 0.5rem;
  }
  
  .stat-card p {
    font-size: 0.875rem;
    color: #6b7c6b;
  }
  
  .timeline {
    position: relative;
  }
  
  .timeline-line {
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #bce4ca;
  }
  
  .timeline-item {
    position: relative;
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .timeline-marker {
    position: relative;
    z-index: 10;
    width: 2rem;
    height: 2rem;
    flex-shrink: 0;
    background-color: #1A6638;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .timeline-marker svg {
    width: 1rem;
    height: 1rem;
    color: white;
  }
  
  .timeline-content {
    flex: 1;
  }
  
  .timeline-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
  }
  
  .timeline-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    background-color: #1A6638;
    color: white;
    border-radius: 6px;
  }
  
  .timeline-content h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0d1f0d;
  }
  
  .timeline-content p {
    color: #6b7c6b;
    line-height: 1.6;
  }
  
  .team-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
  }
  
  @media (max-width: 1024px) {
    .team-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 640px) {
    .team-grid {
      grid-template-columns: 1fr;
    }
  }
  
  .team-card {
    background: white;
    border: 1px solid rgba(26, 102, 56, 0.12);
    border-radius: 10px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: box-shadow 0.3s;
  }
  
  .team-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  }
  
  .team-member-content {
    padding: 1.5rem;
  }
  
  .member-center {
    text-align: center;
    margin-bottom: 1rem;
  }
  
  .avatar {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background-color: #e8f4eb;
    color: #1A6638;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    font-weight: 500;
  }
  
  .member-center h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0d1f0d;
    margin-bottom: 0.25rem;
  }
  
  .member-center .position {
    font-size: 0.875rem;
    color: #1A6638;
    font-weight: 500;
    margin-bottom: 0.25rem;
  }
  
  .member-center .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid rgba(26, 102, 56, 0.12);
    border-radius: 6px;
    margin-bottom: 0.75rem;
  }
  
  .member-center .experience {
    font-size: 0.875rem;
    color: #6b7c6b;
    margin-bottom: 0.75rem;
  }
  
  .specialization h4 {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0d1f0d;
    margin-bottom: 0.5rem;
  }
  
  .specialization-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  
  .tag {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    background-color: #f4f6f4;
    color: #1A6638;
    border-radius: 6px;
  }
  
  .values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
  }
  
  @media (max-width: 1024px) {
    .values-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 640px) {
    .values-grid {
      grid-template-columns: 1fr;
    }
  }
  
  .value-card {
    background: white;
    border: 1px solid rgba(26, 102, 56, 0.12);
    border-radius: 10px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    text-align: center;
  }
  
  .value-icon {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .value-icon svg {
    width: 1.5rem;
    height: 1.5rem;
  }
  
  .value-icon-blue {
    background-color: #dbeafe;
    color: #1d4ed8;
  }
  
  .value-icon-green {
    background-color: #d1fae5;
    color: #047857;
  }
  
  .value-icon-purple {
    background-color: #e9d5ff;
    color: #7c3aed;
  }
  
  .value-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0d1f0d;
    margin-bottom: 0.75rem;
  }
  
  .value-card p {
    font-size: 0.875rem;
    color: #6b7c6b;
    line-height: 1.6;
  }
  
  .cta-card {
    background-color: #f0f9f3;
    border: 1px solid #bce4ca;
    border-radius: 10px;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    padding: 2rem;
    text-align: center;
  }
  
  .cta-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0d1f0d;
    margin-bottom: 1rem;
  }
  
  .cta-card p {
    color: #6b7c6b;
    margin-bottom: 1.5rem;
    max-width: 42rem;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
  }
  
  .cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 2.75rem;
    padding: 0 1.5rem;
    background-color: #1A6638;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-primary:hover {
    background-color: #155530;
  }
  
  .btn-primary svg {
    width: 1.25rem;
    height: 1.25rem;
  }
  
  .btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 2.75rem;
    padding: 0 1.5rem;
    background: white;
    border: 1px solid rgba(26, 102, 56, 0.12);
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #0d1f0d;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-outline:hover {
    background-color: #e8f4eb;
  }
  
  .btn-outline svg {
    width: 1.25rem;
    height: 1.25rem;
  }
  
  @media (max-width: 640px) {
    .hero-content h1 {
      font-size: 2rem;
    }
    
    .hero-content p {
      font-size: 1rem;
    }
    
    .cta-buttons {
      flex-direction: column;
      align-items: stretch;
    }
  }
  </style>
  








