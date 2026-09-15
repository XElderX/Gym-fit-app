import {Navigate,NavLink,Route,Routes} from 'react-router-dom';
import {Activity,BarChart3,BookOpen,CalendarDays,Dumbbell,Settings as SettingsIcon,LayoutTemplate} from 'lucide-react';
import {useAuth} from './context/AuthContext';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import ActiveWorkoutPage from './pages/ActiveWorkoutPage';
import HistoryPage from './pages/HistoryPage';
import ExercisesPage from './pages/ExercisesPage';
import StatisticsPage from './pages/StatisticsPage';
import TemplatesPage from './pages/TemplatesPage';
import SettingsPage from './pages/SettingsPage';

const nav=[['/','Dashboard',Activity],['/workout','Workout',Dumbbell],['/history','History',CalendarDays],['/exercises','Exercises',BookOpen],['/statistics','Stats',BarChart3],['/templates','Templates',LayoutTemplate],['/settings','Settings',SettingsIcon]] as const;
function Shell(){return <div className="app-shell"><aside><div className="brand"><span>GYM</span> FIT</div><nav>{nav.map(([to,label,Icon])=><NavLink key={to} to={to} end={to==='/'}><Icon size={20}/><span>{label}</span></NavLink>)}</nav></aside><main><Routes><Route path="/" element={<DashboardPage/>}/><Route path="/workout" element={<ActiveWorkoutPage/>}/><Route path="/history" element={<HistoryPage/>}/><Route path="/exercises" element={<ExercisesPage/>}/><Route path="/statistics" element={<StatisticsPage/>}/><Route path="/templates" element={<TemplatesPage/>}/><Route path="/settings" element={<SettingsPage/>}/><Route path="*" element={<Navigate to="/"/>}/></Routes></main><div className="bottom-nav">{nav.slice(0,5).map(([to,label,Icon])=><NavLink key={to} to={to} end={to==='/'}><Icon size={20}/><span>{label}</span></NavLink>)}</div></div>}
export default function App(){const{user,loading}=useAuth();if(loading)return <div className="center">Loading…</div>;return user?<Shell/>:<LoginPage/>}
