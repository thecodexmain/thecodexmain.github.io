import Navbar from "@/components/Navbar";
import HeroSection from "@/components/HeroSection";
import TrustBadges from "@/components/TrustBadges";
import AboutSection from "@/components/AboutSection";
import ServicesSection from "@/components/ServicesSection";
import StepsSection from "@/components/StepsSection";
import PaymentModelSection from "@/components/PaymentModelSection";
import WhyChooseUs from "@/components/WhyChooseUs";
import RegionsSection from "@/components/RegionsSection";
import WarningSection from "@/components/WarningSection";
import TelegramSection from "@/components/TelegramSection";
import CTASection from "@/components/CTASection";
import Footer from "@/components/Footer";

export default function Home() {
  return (
    <main>
      <Navbar />
      <HeroSection />
      <TrustBadges />
      <AboutSection />
      <ServicesSection />
      <StepsSection />
      <PaymentModelSection />
      <WhyChooseUs />
      <RegionsSection />
      <WarningSection />
      <TelegramSection />
      <CTASection />
      <Footer />
    </main>
  );
}
