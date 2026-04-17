"use client";
import { motion } from "framer-motion";
import { LucideIcon } from "lucide-react";

interface FeatureCardProps {
  icon: LucideIcon;
  title: string;
  description: string;
  iconColor?: string;
}

export default function FeatureCard({ icon: Icon, title, description, iconColor = "text-cyan-400" }: FeatureCardProps) {
  return (
    <motion.div
      whileHover={{ y: -8, boxShadow: "0 0 30px rgba(6,182,212,0.25)" }}
      className="p-6 rounded-2xl border flex flex-col gap-4"
      style={{
        background: "rgba(15,23,42,0.6)",
        borderColor: "rgba(255,255,255,0.08)",
        backdropFilter: "blur(20px)",
      }}
    >
      <div
        className="w-12 h-12 rounded-xl flex items-center justify-center"
        style={{ background: "rgba(6,182,212,0.1)" }}
      >
        <Icon className={`w-6 h-6 ${iconColor}`} />
      </div>
      <h3 className="text-white font-semibold text-lg">{title}</h3>
      <p className="text-gray-400 text-sm leading-relaxed">{description}</p>
    </motion.div>
  );
}
