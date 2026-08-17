import React, { useState, useMemo, useEffect } from 'react';
import { HARDWARE_CATALOG } from './catalog';
import '../resources/css/app.css';

const getCategoryMaxPrice = (categoryKey) => {
  const items = HARDWARE_CATALOG[categoryKey];
  if (!items || items.length === 0) return 10000;
  return Math.max(...items.map(i => i.price));
};

export default function BuildCorePrototype() {
  const [activeTab, setActiveTab] = useState('manual');
  
  // Catalog Browser State & Filters
  const [browseCategory, setBrowseCategory] = useState('cpus');
  const [catalogSearch, setCatalogSearch] = useState('');
  const [catalogMaxPrice, setCatalogMaxPrice] = useState(() => getCategoryMaxPrice('cpus'));
  const [catalogSocket, setCatalogSocket] = useState('All');
  const [catalogRamType, setCatalogRamType] = useState('All');

  // Auto-Build State
  const [budget, setBudget] = useState(6500);
  const [useCase, setUseCase] = useState('gaming');
  const [resolution, setResolution] = useState('1080p');
  
  // Authentication State
  const [currentUser, setCurrentUser] = useState(null);
  const [authModal, setAuthModal] = useState(null); 
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [username, setUsername] = useState('');
  const [savedBuilds, setSavedBuilds] = useState([]);

  // Tooltip & Picker State
  const [tooltip, setTooltip] = useState({ visible: false, x: 0, y: 0, data: null });
  const [pickerCategory, setPickerCategory] = useState(null);
  const [pickerSearch, setPickerSearch] = useState('');
  const [pickerMaxPrice, setPickerMaxPrice] = useState(10000);
  
  // Granular Compatibility Filters
  const [compatFilters, setCompatFilters] = useState({
    socket: false,
    thermal: false,
    ram: false,
    power: false,
    spatial: false
  });

  // Hardware State
  const [build, setBuild] = useState({
    cpu: HARDWARE_CATALOG.cpus[0],
    cooler: HARDWARE_CATALOG.coolers[0],
    mobo: HARDWARE_CATALOG.motherboards[0],
    ram: HARDWARE_CATALOG.rams[0],
    gpu: HARDWARE_CATALOG.gpus[0],
    psu: HARDWARE_CATALOG.psus[0],
    case: HARDWARE_CATALOG.cases[0]
  });

  useEffect(() => {
    const session = JSON.parse(localStorage.getItem('buildcore_session'));
    if (session) {
      setCurrentUser(session);
      loadUserBuilds(session.email);
    }
  }, []);

  // --- Core Validation Engines (CLP) ---
  const clpRules = useMemo(() => {
    const estPower = build.cpu.tdp + build.gpu.tdp + 50;
    const requiredThermalCapacity = Math.round(build.cpu.tdp * 1.1); 

    return {
      socketMatch: build.cpu.socket === build.mobo.socket,
      ramMatch: build.ram.type === build.mobo.ramType,
      thermalFit: build.cooler.maxTdp >= requiredThermalCapacity,
      powerMargin: build.psu.wattage >= estPower * 1.25,
      spatialFit: build.gpu.lengthMm <= build.case.maxGpuLengthMm,
      estPower,
      requiredThermalCapacity
    };
  }, [build]);

  // --- System Bottleneck Calculator ---
  const bottleneckStatus = useMemo(() => {
    const cpuScore = build.cpu.score;
    const gpuScore = build.gpu.score;
    const difference = gpuScore - cpuScore;

    if (difference > 18) return { status: 'CPU Bottleneck', message: 'Processor is too weak for this GPU. You will lose frames.', color: 'text-amber-400', border: 'border-amber-400/30', bg: 'bg-amber-400/10', icon: '⚠️' };
    if (difference < -22) return { status: 'GPU Bottleneck', message: 'Graphics card is holding back your powerful CPU.', color: 'text-amber-400', border: 'border-amber-400/30', bg: 'bg-amber-400/10', icon: '⚠️' };
    return { status: 'Balanced System', message: 'CPU and GPU performance are perfectly matched.', color: 'text-emerald-400', border: 'border-emerald-500/30', bg: 'bg-emerald-500/10', icon: '✓' };
  }, [build]);

  // --- Benchmark Engine ---
  const benchmarkScores = useMemo(() => {
    const cpuScore = build.cpu?.score || 0;
    const gpuScore = build.gpu?.score || 0;
    const ramScore = build.ram?.score || 0;

    const gaming = Math.round((gpuScore * 0.65 + cpuScore * 0.25 + ramScore * 0.10) * 115);
    const productivity = Math.round((cpuScore * 0.50 + ramScore * 0.30 + gpuScore * 0.20) * 115);
    const overall = Math.round((gaming * 0.55) + (productivity * 0.45));

    let tier = 'Entry-Level (1080p Low)';
    if (gaming >= 10500) tier = 'God Tier (4K Ultra)';
    else if (gaming >= 8800) tier = 'Enthusiast (1440p Ultra)';
    else if (gaming >= 7200) tier = 'High Performance (1440p High)';
    else if (gaming >= 5500) tier = 'Mid-Range (1080p High)';

    const MAX_THEORETICAL_SCORE = 11500;
    const percentage = Math.min(100, (overall / MAX_THEORETICAL_SCORE) * 100);
    const circumference = 2 * Math.PI * 28;
    const strokeDashoffset = circumference - (percentage / 100) * circumference;

    return { gaming, productivity, overall, tier, percentage, circumference, strokeDashoffset };
  }, [build]);

  // --- Dynamic Diagnostic Suggestions ---
  const activeSuggestions = useMemo(() => {
    const suggestions = [];
    
    if (!clpRules.socketMatch) suggestions.push(`Swap Motherboard to an ${build.cpu.socket} socket, OR change CPU to fit ${build.mobo.socket}.`);
    if (!clpRules.thermalFit) suggestions.push(`Upgrade CPU Cooler to support at least ${clpRules.requiredThermalCapacity}W of heat dissipation.`);
    if (!clpRules.ramMatch) suggestions.push(`Change Memory to ${build.mobo.ramType} to match the selected motherboard.`);
    if (!clpRules.powerMargin) suggestions.push(`Upgrade Power Supply to at least ${Math.round(clpRules.estPower * 1.25)}W.`);
    if (!clpRules.spatialFit) suggestions.push(`Choose a larger Case (with >${build.gpu.lengthMm}mm clearance), OR pick a shorter GPU.`);
    
    if (bottleneckStatus.status === 'CPU Bottleneck') suggestions.push(`Upgrade your CPU to match the ${build.gpu.name}, OR downgrade the GPU to save money.`);
    else if (bottleneckStatus.status === 'GPU Bottleneck') suggestions.push(`Upgrade your GPU to utilize the ${build.cpu.name}, OR downgrade the CPU to save money.`);

    return suggestions;
  }, [clpRules, bottleneckStatus, build]);

  const allRulesPass = Object.values(clpRules).slice(0, 5).every(Boolean);
  const totalCost = Object.values(build).reduce((sum, item) => sum + (item.price || 0), 0);

  // --- Catalog Browser Filtering Logic ---
  const handleCategoryChange = (catId) => {
    setBrowseCategory(catId);
    setCatalogSearch('');
    setCatalogMaxPrice(getCategoryMaxPrice(catId)); 
    setCatalogSocket('All');
    setCatalogRamType('All');
  };

  const filteredCatalogItems = useMemo(() => {
    let items = HARDWARE_CATALOG[browseCategory] || [];

    if (catalogSearch) items = items.filter(i => i.name.toLowerCase().includes(catalogSearch.toLowerCase()));
    items = items.filter(i => i.price <= catalogMaxPrice);
    if (catalogSocket !== 'All' && ['cpus', 'motherboards'].includes(browseCategory)) items = items.filter(i => i.socket === catalogSocket);
    if (catalogRamType !== 'All' && ['motherboards', 'rams'].includes(browseCategory)) items = items.filter(i => (i.ramType === catalogRamType || i.type === catalogRamType));

    return items;
  }, [browseCategory, catalogSearch, catalogMaxPrice, catalogSocket, catalogRamType]);

  // --- Marketplace Modal Granular Filtering Logic ---
  const checkCompatibility = (item, cat, currentBuild) => {
    let isValid = true;
    
    if (cat === 'cpu') {
      if (compatFilters.socket && item.socket !== currentBuild.mobo.socket) isValid = false;
      if (compatFilters.thermal && currentBuild.cooler.maxTdp < item.tdp * 1.1) isValid = false;
    }
    if (cat === 'cooler') {
      if (compatFilters.thermal && item.maxTdp < currentBuild.cpu.tdp * 1.1) isValid = false;
    }
    if (cat === 'mobo') {
      if (compatFilters.socket && item.socket !== currentBuild.cpu.socket) isValid = false;
      if (compatFilters.ram && item.ramType !== currentBuild.ram.type) isValid = false;
    }
    if (cat === 'ram') {
      if (compatFilters.ram && item.type !== currentBuild.mobo.ramType) isValid = false;
    }
    if (cat === 'gpu') {
      if (compatFilters.spatial && item.lengthMm > currentBuild.case.maxGpuLengthMm) isValid = false;
      if (compatFilters.power && currentBuild.psu.wattage < (currentBuild.cpu.tdp + item.tdp + 50) * 1.25) isValid = false;
    }
    if (cat === 'psu') {
      if (compatFilters.power && item.wattage < (currentBuild.cpu.tdp + currentBuild.gpu.tdp + 50) * 1.25) isValid = false;
    }
    if (cat === 'case') {
      if (compatFilters.spatial && item.maxGpuLengthMm < currentBuild.gpu.lengthMm) isValid = false;
    }

    return isValid;
  };

  const pickerItems = useMemo(() => {
    if (!pickerCategory) return [];
    const listMap = { cpu: 'cpus', cooler: 'coolers', mobo: 'motherboards', ram: 'rams', gpu: 'gpus', psu: 'psus', case: 'cases' };
    let items = HARDWARE_CATALOG[listMap[pickerCategory]] || [];

    if (pickerSearch) items = items.filter(i => i.name.toLowerCase().includes(pickerSearch.toLowerCase()));
    items = items.filter(i => i.price <= pickerMaxPrice);
    
    // Always run through the compatibility engine, which obeys the granular user selections
    items = items.filter(item => checkCompatibility(item, pickerCategory, build));

    return items;
  }, [pickerCategory, pickerSearch, pickerMaxPrice, compatFilters, build]);

  // --- Event Handlers ---
  const openPicker = (category) => {
    setPickerCategory(category);
    setPickerSearch('');
    
    const listMap = { cpu: 'cpus', cooler: 'coolers', mobo: 'motherboards', ram: 'rams', gpu: 'gpus', psu: 'psus', case: 'cases' };
    setPickerMaxPrice(getCategoryMaxPrice(listMap[category])); 
    
    // Reset all compatibility filters when opening a new category
    setCompatFilters({ socket: false, thermal: false, ram: false, power: false, spatial: false });
  };

  const selectComponent = (item) => {
    setBuild(prev => ({ ...prev, [pickerCategory]: item }));
    setPickerCategory(null);
  };

  const addFromCatalog = (item, catalogCategory) => {
    const catMap = { cpus: 'cpu', coolers: 'cooler', motherboards: 'mobo', rams: 'ram', gpus: 'gpu', psus: 'psu', cases: 'case' };
    setBuild(prev => ({ ...prev, [catMap[catalogCategory]]: item }));
    setActiveTab('manual');
  };

  const handleMouseMove = (e, item) => {
    if (pickerCategory) return;
    setTooltip({ visible: true, x: e.clientX + 15, y: e.clientY + 15, data: item });
  };
  const handleMouseLeave = () => setTooltip({ ...tooltip, visible: false });

  // --- Authentication & Saving ---
  const handleAuth = (e) => {
    e.preventDefault();
    const usersDB = JSON.parse(localStorage.getItem('buildcore_users')) || [];

    if (authModal === 'signup') {
      if (usersDB.find(u => u.email === email)) return alert("Email already registered.");
      const newUser = { username, email, password };
      usersDB.push(newUser);
      localStorage.setItem('buildcore_users', JSON.stringify(usersDB));
      setCurrentUser(newUser);
      localStorage.setItem('buildcore_session', JSON.stringify(newUser));
      alert("Account created successfully!");
    } else {
      const user = usersDB.find(u => u.email === email && u.password === password);
      if (!user) return alert("Invalid credentials.");
      setCurrentUser(user);
      localStorage.setItem('buildcore_session', JSON.stringify(user));
      loadUserBuilds(user.email);
    }
    closeModal();
  };

  const handleLogout = () => {
    setCurrentUser(null);
    setSavedBuilds([]);
    localStorage.removeItem('buildcore_session');
    setActiveTab('manual');
  };

  const closeModal = () => {
    setAuthModal(null);
    setEmail('');
    setPassword('');
    setUsername('');
  };

  const saveCurrentBuild = () => {
    if (!currentUser) return setAuthModal('login');
    if (!allRulesPass) return alert("Cannot save a build with unresolved conflicts.");

    const buildName = prompt("Enter a name for this build:", "My Custom Rig");
    if (!buildName) return;

    const allBuilds = JSON.parse(localStorage.getItem('buildcore_builds')) || [];
    allBuilds.push({
      id: Date.now(), userEmail: currentUser.email, name: buildName,
      date: new Date().toLocaleDateString(), components: build, cost: totalCost, scores: benchmarkScores
    });
    localStorage.setItem('buildcore_builds', JSON.stringify(allBuilds));
    loadUserBuilds(currentUser.email);
    alert("Build saved securely to your workspace!");
  };

  const loadUserBuilds = (userEmail) => {
    const allBuilds = JSON.parse(localStorage.getItem('buildcore_builds')) || [];
    setSavedBuilds(allBuilds.filter(b => b.userEmail === userEmail));
  };

  const deleteBuild = (buildId) => {
    let allBuilds = JSON.parse(localStorage.getItem('buildcore_builds')) || [];
    localStorage.setItem('buildcore_builds', JSON.stringify(allBuilds.filter(b => b.id !== buildId)));
    loadUserBuilds(currentUser.email);
  };

  // --- Auto-Builder ---
  const runAutoBuild = () => {
    let bestCombination = null;
    let maxPerformance = -1;
    let cpuW = 1.0, gpuW = 1.0, ramW = 1.0;
    
    if (useCase === 'gaming') gpuW = 1.6; 
    else if (useCase === 'video-editing') { cpuW = 1.5; ramW = 1.4; } 
    else if (useCase === 'office') gpuW = 0.2; 

    let minGpuScore = 0;
    if (resolution === '2k') minGpuScore = 80; 
    if (resolution === '4k') minGpuScore = 95; 

    for (let cpu of HARDWARE_CATALOG.cpus) {
      for (let cooler of HARDWARE_CATALOG.coolers) {
        if (cooler.maxTdp < cpu.tdp * 1.1) continue; 
        for (let mobo of HARDWARE_CATALOG.motherboards) {
          if (cpu.socket !== mobo.socket) continue;
          for (let ram of HARDWARE_CATALOG.rams) {
            if (ram.type !== mobo.ramType) continue;
            for (let gpu of HARDWARE_CATALOG.gpus) {
              if (gpu.score < minGpuScore) continue; 
              for (let psu of HARDWARE_CATALOG.psus) {
                if (psu.wattage < (cpu.tdp + gpu.tdp + 50) * 1.25) continue;
                for (let caseItem of HARDWARE_CATALOG.cases) {
                  if (gpu.lengthMm > caseItem.maxGpuLengthMm) continue;

                  const cost = cpu.price + cooler.price + mobo.price + ram.price + gpu.price + psu.price + caseItem.price;
                  if (cost <= budget) {
                    const score = (cpu.score * cpuW) + cooler.score + mobo.score + (ram.score * ramW) + (gpu.score * gpuW) + psu.score + caseItem.score;
                    if (Math.abs(gpu.score - cpu.score) <= 22 && score > maxPerformance) {
                      maxPerformance = score;
                      bestCombination = { cpu, cooler, mobo, ram, gpu, psu, case: caseItem };
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    if (bestCombination) {
      setBuild(bestCombination);
      setActiveTab('manual');
    } else {
      alert(`No valid configuration found for ${resolution.toUpperCase()} ${useCase.replace('-', ' ')} at RM ${budget}. Please increase your budget or lower your target resolution.`);
    }
  };

  return (
    <div className="app-container relative">
      <header className="header-container">
        <div>
          <h1 className="header-title">YC's PC Building Platform</h1>
          <p className="text-zinc-500 text-sm font-medium mt-1">Prototype Building Platform</p>
        </div>
        <div className="flex items-center gap-4">
          {currentUser ? (
            <div className="flex items-center gap-4 bg-zinc-900 border border-zinc-800 px-4 py-2 rounded-xl">
              <span className="text-sm text-zinc-300">👤 {currentUser.username}</span>
              <div className="w-px h-4 bg-zinc-700"></div>
              <button onClick={handleLogout} className="text-sm text-rose-400 hover:text-rose-300 font-bold">Logout</button>
            </div>
          ) : (
            <div className="flex gap-2">
              <button onClick={() => setAuthModal('login')} className="px-4 py-2 text-sm font-bold text-zinc-300 hover:text-white transition-colors">Login</button>
              <button onClick={() => setAuthModal('signup')} className="px-4 py-2 text-sm font-bold bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors">Sign Up</button>
            </div>
          )}
        </div>
      </header>

      <main className="max-w-6xl mx-auto">
        <div className="tab-container">
          <button onClick={() => setActiveTab('manual')} className={`tab-btn ${activeTab === 'manual' ? 'tab-active' : 'tab-inactive'}`}>
            Manual Builder
          </button>
          <button onClick={() => setActiveTab('catalog')} className={`tab-btn ${activeTab === 'catalog' ? 'tab-active' : 'tab-inactive'}`}>
            Hardware Catalog
          </button>
          <button onClick={() => setActiveTab('autobuild')} className={`tab-btn ${activeTab === 'autobuild' ? 'tab-autobuild' : 'tab-inactive'}`}>
            Auto-Build
          </button>
          {currentUser && (
            <button onClick={() => setActiveTab('saved')} className={`tab-btn ${activeTab === 'saved' ? 'tab-active' : 'tab-inactive'}`}>
              Saved Builds ({savedBuilds.length})
            </button>
          )}
        </div>

        {/* View: Manual Builder */}
        {activeTab === 'manual' && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div className="lg:col-span-2 space-y-4">
              <div className="card">
                <h2 className="section-title"><span className="w-2 h-6 bg-blue-500 rounded-full"></span> Configuration</h2>
                <div className="space-y-3">
                  {['cpu', 'cooler', 'mobo', 'ram', 'gpu', 'psu', 'case'].map((category) => (
                    <div 
                      key={category} 
                      className="flex items-center justify-between p-4 rounded-2xl bg-zinc-900/50 border border-zinc-800 hover:border-zinc-700 transition-colors"
                      onMouseMove={(e) => handleMouseMove(e, build[category])}
                      onMouseLeave={handleMouseLeave}
                    >
                      <div className="flex flex-col">
                        <span className="text-[11px] text-zinc-500 uppercase tracking-widest font-bold">{category}</span>
                        <span className="text-white font-bold text-lg">{build[category].name}</span>
                        <span className="text-emerald-400 font-semibold text-sm">RM {build[category].price}</span>
                      </div>
                      <button 
                        onClick={() => openPicker(category)} 
                        className="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm"
                      >
                        Change Part
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="space-y-6">
              <div className="card sticky top-6">
                <h2 className="text-lg font-bold text-white mb-6">Build Summary</h2>
                
                <div className="flex justify-between items-end mb-6 pb-6 border-b border-zinc-800">
                  <span className="text-zinc-500 font-medium">Est. Total</span>
                  <span className="text-4xl font-black text-white">RM {totalCost}</span>
                </div>

                <div className="mb-6 p-5 rounded-2xl bg-gradient-to-br from-zinc-900 to-zinc-950 border border-zinc-800 flex items-center justify-between shadow-inner">
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <span className="text-xs text-zinc-400 uppercase font-bold tracking-wider">Estimated Score</span>
                      <span className="text-[10px] font-bold text-blue-400 px-2 py-0.5 rounded-full bg-blue-500/10 border border-blue-500/20">
                        {benchmarkScores.tier}
                      </span>
                    </div>
                    <div className="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">
                      {benchmarkScores.overall.toLocaleString()} <span className="text-sm font-normal text-zinc-500">pts</span>
                    </div>
                    <div className="flex gap-4 text-xs mt-2 text-zinc-400">
                      <span>Gaming: <strong className="text-zinc-200">{benchmarkScores.gaming}</strong></span>
                      <span>Productivity: <strong className="text-zinc-200">{benchmarkScores.productivity}</strong></span>
                    </div>
                  </div>
                  
                  <div className="relative w-20 h-20 flex items-center justify-center flex-shrink-0">
                    <svg className="transform -rotate-90 w-20 h-20">
                      <circle cx="40" cy="40" r="28" stroke="currentColor" strokeWidth="6" fill="transparent" className="text-zinc-800" />
                      <circle 
                        cx="40" cy="40" r="28" stroke="currentColor" strokeWidth="6" fill="transparent" 
                        strokeDasharray={benchmarkScores.circumference} 
                        strokeDashoffset={benchmarkScores.strokeDashoffset} 
                        strokeLinecap="round" 
                        className="text-indigo-500 transition-all duration-1000 ease-out" 
                      />
                    </svg>
                    <div className="absolute flex flex-col items-center justify-center font-bold text-xs text-zinc-200">
                      {Math.round(benchmarkScores.percentage)}%
                    </div>
                  </div>
                </div>

                <h3 className="text-xs text-zinc-500 uppercase tracking-widest font-bold mb-4">Constraint Engine</h3>
                <ul className="space-y-3 text-sm font-medium">
                  <li className={`flex items-start gap-3 p-3 rounded-xl border ${bottleneckStatus.bg} ${bottleneckStatus.border} ${bottleneckStatus.color}`}>
                    <span className="mt-0.5">{bottleneckStatus.icon}</span>
                    <div className="flex flex-col">
                      <span className="font-bold">{bottleneckStatus.status}</span>
                      <span className="text-xs opacity-80 font-normal">{bottleneckStatus.message}</span>
                    </div>
                  </li>
                  <li className={`validation-item ${clpRules.socketMatch ? 'validation-pass' : 'validation-fail'}`}>
                    <span className="mt-0.5">{clpRules.socketMatch ? '✓' : '✕'}</span>
                    <span>{clpRules.socketMatch ? 'Socket Compatibility' : 'Socket Mismatch'}</span>
                  </li>
                  <li className={`validation-item ${clpRules.thermalFit ? 'validation-pass' : 'validation-fail'}`}>
                    <span className="mt-0.5">{clpRules.thermalFit ? '✓' : '✕'}</span>
                    <span>{clpRules.thermalFit ? `Thermal Validation (${build.cooler.maxTdp}W Cooling)` : `Cooler Limit Reached (Need >${clpRules.requiredThermalCapacity}W)`}</span>
                  </li>
                  <li className={`validation-item ${clpRules.ramMatch ? 'validation-pass' : 'validation-fail'}`}>
                    <span className="mt-0.5">{clpRules.ramMatch ? '✓' : '✕'}</span>
                    <span>{clpRules.ramMatch ? 'Memory Generation' : 'RAM Mismatch'}</span>
                  </li>
                  <li className={`validation-item ${clpRules.powerMargin ? 'validation-pass' : 'validation-fail'}`}>
                    <span className="mt-0.5">{clpRules.powerMargin ? '✓' : '✕'}</span>
                    <span>{clpRules.powerMargin ? `Power Margin (${build.psu.wattage}W)` : `Insufficient Wattage`}</span>
                  </li>
                  <li className={`validation-item ${clpRules.spatialFit ? 'validation-pass' : 'validation-fail'}`}>
                    <span className="mt-0.5">{clpRules.spatialFit ? '✓' : '✕'}</span>
                    <span>{clpRules.spatialFit ? 'Spatial Clearance' : `GPU Collision`}</span>
                  </li>
                </ul>

                {activeSuggestions.length > 0 && (
                  <div className="mt-6 p-4 rounded-xl border border-amber-500/30 bg-amber-500/5">
                    <h4 className="text-xs font-bold text-amber-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                      <span>💡</span> Resolution Suggestions
                    </h4>
                    <ul className="space-y-2 text-sm text-zinc-300">
                      {activeSuggestions.map((suggestion, idx) => (
                        <li key={idx} className="flex items-start gap-2">
                          <span className="text-amber-500 mt-0.5">•</span>
                          <span>{suggestion}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

                <div className={`status-badge mb-4 mt-6 ${allRulesPass && bottleneckStatus.status === 'Balanced System' ? 'status-optimal' : 'status-conflict'}`}>
                  {allRulesPass && bottleneckStatus.status === 'Balanced System' ? 'SYSTEM OPTIMAL' : 'CONFLICTS / BOTTLENECKS DETECTED'}
                </div>
                <button onClick={saveCurrentBuild} className="w-full py-3 bg-zinc-800 hover:bg-zinc-700 text-white font-bold rounded-xl border border-zinc-700 transition-colors">
                  💾 Save Configuration
                </button>
              </div>
            </div>
          </div>
        )}

        {/* View: Hardware Catalog Browser */}
        {activeTab === 'catalog' && (
          <div className="card">
            <h2 className="section-title"><span className="w-2 h-6 bg-blue-500 rounded-full"></span> Hardware Catalog</h2>
            
            <div className="flex flex-wrap gap-2 mb-6">
              {[
                { id: 'cpus', label: 'Processors (CPU)' },
                { id: 'coolers', label: 'CPU Coolers' },
                { id: 'motherboards', label: 'Motherboards' },
                { id: 'rams', label: 'Memory (RAM)' },
                { id: 'gpus', label: 'Graphics Cards (GPU)' },
                { id: 'psus', label: 'Power Supplies' },
                { id: 'cases', label: 'Cases / Chassis' }
              ].map(cat => (
                <button 
                  key={cat.id}
                  onClick={() => handleCategoryChange(cat.id)}
                  className={`px-4 py-2 rounded-xl text-sm font-bold transition-colors border ${browseCategory === cat.id ? 'bg-zinc-800 border-zinc-700 text-white' : 'bg-[#09090b] border-transparent text-zinc-500 hover:text-zinc-300 hover:bg-zinc-900'}`}
                >
                  {cat.label}
                </button>
              ))}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 bg-zinc-900/50 p-5 rounded-2xl border border-zinc-800">
              <div>
                <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Search</label>
                <input 
                  type="text" 
                  placeholder="Search parts..." 
                  value={catalogSearch} 
                  onChange={e => setCatalogSearch(e.target.value)} 
                  className="w-full bg-[#09090b] border border-zinc-700 text-white p-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500" 
                />
              </div>
              
              <div>
                <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Max Price: RM {catalogMaxPrice}</label>
                <input 
                  type="range" 
                  min="50" 
                  max={getCategoryMaxPrice(browseCategory)} 
                  step="10" 
                  value={catalogMaxPrice} 
                  onChange={(e) => setCatalogMaxPrice(Number(e.target.value))} 
                  className="range-slider w-full mt-2" 
                />
              </div>

              {['cpus', 'motherboards'].includes(browseCategory) && (
                <div>
                  <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Socket Type</label>
                  <select 
                    value={catalogSocket} 
                    onChange={e => setCatalogSocket(e.target.value)} 
                    className="w-full bg-[#09090b] border border-zinc-700 text-zinc-200 p-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500 cursor-pointer"
                  >
                    <option value="All">All Sockets</option>
                    <option value="AM4">AM4 (AMD)</option>
                    <option value="AM5">AM5 (AMD)</option>
                    <option value="LGA1700">LGA1700 (Intel)</option>
                  </select>
                </div>
              )}

              {['rams', 'motherboards'].includes(browseCategory) && (
                <div>
                  <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Memory Generation</label>
                  <select 
                    value={catalogRamType} 
                    onChange={e => setCatalogRamType(e.target.value)} 
                    className="w-full bg-[#09090b] border border-zinc-700 text-zinc-200 p-2.5 rounded-xl text-sm focus:outline-none focus:border-blue-500 cursor-pointer"
                  >
                    <option value="All">All Types</option>
                    <option value="DDR4">DDR4</option>
                    <option value="DDR5">DDR5</option>
                  </select>
                </div>
              )}
            </div>

            {filteredCatalogItems.length === 0 ? (
              <div className="text-center text-zinc-500 py-12">No components match your current filters.</div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                {filteredCatalogItems.map(item => (
                  <div key={item.id} className="bg-[#09090b] border border-zinc-800 rounded-2xl p-5 hover:border-zinc-700 transition-colors flex flex-col justify-between">
                    <div>
                      <h3 className="text-white font-bold text-base leading-tight mb-4">{item.name}</h3>
                      
                      <div className="space-y-1.5 text-xs mb-6">
                        {item.socket && <div className="flex justify-between"><span className="text-zinc-500">Socket:</span><span className="text-zinc-300 font-medium">{item.socket}</span></div>}
                        {item.tdp && <div className="flex justify-between"><span className="text-zinc-500">TDP:</span><span className="text-zinc-300 font-medium">{item.tdp}W</span></div>}
                        {item.maxTdp && <div className="flex justify-between"><span className="text-zinc-500">Max Cooling:</span><span className="text-zinc-300 font-medium">{item.maxTdp}W</span></div>}
                        {item.ramType && <div className="flex justify-between"><span className="text-zinc-500">RAM Gen:</span><span className="text-zinc-300 font-medium">{item.ramType}</span></div>}
                        {item.type && <div className="flex justify-between"><span className="text-zinc-500">Type:</span><span className="text-zinc-300 font-medium">{item.type}</span></div>}
                        {item.lengthMm && <div className="flex justify-between"><span className="text-zinc-500">Length:</span><span className="text-zinc-300 font-medium">{item.lengthMm}mm</span></div>}
                        {item.wattage && <div className="flex justify-between"><span className="text-zinc-500">Wattage:</span><span className="text-zinc-300 font-medium">{item.wattage}W</span></div>}
                        {item.maxGpuLengthMm && <div className="flex justify-between"><span className="text-zinc-500">Max GPU Clearance:</span><span className="text-zinc-300 font-medium">{item.maxGpuLengthMm}mm</span></div>}
                      </div>
                    </div>
                    
                    <div className="flex items-center justify-between pt-4 border-t border-zinc-800">
                      <span className="text-emerald-400 font-black text-lg">RM {item.price}</span>
                      <button 
                        onClick={() => addFromCatalog(item, browseCategory)}
                        className="px-3 py-1.5 bg-blue-600/10 hover:bg-blue-600/20 border border-blue-500/20 text-blue-400 text-xs font-bold rounded-lg transition-colors"
                      >
                        Add to Build
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* View: MCKP Auto-Build */}
        {activeTab === 'autobuild' && (
          <div className="card md:p-12 max-w-3xl mx-auto text-center">
            <div className="w-16 h-16 mx-auto bg-blue-500/10 text-blue-400 flex items-center justify-center rounded-2xl mb-6">
              <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h2 className="text-3xl font-black text-white mb-4">Auto-Build Generator</h2>
            <p className="text-zinc-400 text-sm md:text-base mb-10 max-w-lg mx-auto">
              Set your target budget and primary parameters. Our combinatorial solver will instantly evaluate the catalog to generate a performance-maximized, bottleneck-free configuration tailored to your workload.
            </p>
            
            <div className="flex flex-col items-center bg-[#09090b] p-8 rounded-3xl border border-zinc-800/50">
              <div className="flex flex-col md:flex-row gap-6 w-full max-w-xl mx-auto mb-10 pb-10 border-b border-zinc-800">
                <div className="flex-1 text-left">
                  <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Primary Workload</label>
                  <select value={useCase} onChange={e => setUseCase(e.target.value)} className="w-full bg-[#121214] border border-zinc-700 text-zinc-200 p-3 rounded-xl text-sm font-medium focus:outline-none focus:border-blue-500">
                    <option value="gaming">Gaming</option>
                    <option value="video-editing">Video Editing & Rendering</option>
                    <option value="office">General Office & Web</option>
                  </select>
                </div>
                <div className="flex-1 text-left">
                  <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Target Resolution</label>
                  <select value={resolution} onChange={e => setResolution(e.target.value)} className="w-full bg-[#121214] border border-zinc-700 text-zinc-200 p-3 rounded-xl text-sm font-medium focus:outline-none focus:border-blue-500">
                    <option value="1080p">1080p (FHD)</option>
                    <option value="2k">1440p (2K / QHD)</option>
                    <option value="4k">2160p (4K / UHD)</option>
                  </select>
                </div>
              </div>

              <div className="w-full max-w-xl space-y-6">
                <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide">Maximum Budget (RM)</label>
                <div className="text-6xl font-black bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-indigo-500 pb-2">
                  RM {budget}
                </div>
                <input type="range" min="3000" max="15000" step="100" value={budget} onChange={(e) => setBudget(Number(e.target.value))} className="range-slider w-full" />
              </div>

              <div className="mt-12 w-full">
                <button onClick={runAutoBuild} className="btn-primary">Generate Optimized Build</button>
              </div>
            </div>
          </div>
        )}

        {/* View: Saved Builds */}
        {activeTab === 'saved' && currentUser && (
          <div className="card">
            <h2 className="section-title"><span className="w-2 h-6 bg-blue-500 rounded-full"></span> Your Workspace</h2>
            {savedBuilds.length === 0 ? (
              <p className="text-zinc-500 py-10 text-center">You have not saved any builds yet.</p>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {savedBuilds.map(b => (
                  <div key={b.id} className="bg-[#09090b] border border-zinc-800 rounded-2xl p-6">
                    <div className="flex justify-between items-start mb-4">
                      <div>
                        <h3 className="text-lg font-bold text-white">{b.name}</h3>
                        <p className="text-xs text-zinc-500">Saved on {b.date}</p>
                      </div>
                      <span className="text-xl font-black text-emerald-400">RM {b.cost}</span>
                    </div>
                    
                    {b.scores && (
                      <div className="mb-4 p-3 bg-zinc-900/60 rounded-xl border border-zinc-800 text-xs flex justify-between items-center">
                        <div>
                          <span className="text-zinc-500 block">Benchmark Rating</span>
                          <span className="font-bold text-blue-400">{b.scores.overall.toLocaleString()} pts</span>
                        </div>
                        <span className="text-[10px] bg-zinc-800 text-zinc-300 px-2 py-1 rounded font-semibold">{b.scores.tier}</span>
                      </div>
                    )}

                    <div className="space-y-1 text-sm text-zinc-400 mb-6">
                      <p><strong className="text-zinc-300">CPU:</strong> {b.components.cpu.name}</p>
                      <p><strong className="text-zinc-300">GPU:</strong> {b.components.gpu.name}</p>
                      <p><strong className="text-zinc-300">Mobo:</strong> {b.components.mobo.name}</p>
                    </div>
                    <div className="flex gap-2">
                      <button onClick={() => { setBuild(b.components); setActiveTab('manual'); }} className="flex-1 py-2 bg-blue-600/10 text-blue-400 font-bold rounded-xl hover:bg-blue-600/20 transition-colors">Load Build</button>
                      <button onClick={() => deleteBuild(b.id)} className="px-4 py-2 bg-rose-500/10 text-rose-400 font-bold rounded-xl hover:bg-rose-500/20 transition-colors">Delete</button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </main>

      {/* PART PICKER / MARKETPLACE MODAL */}
      {pickerCategory && (
        <div className="fixed inset-0 z-[80] bg-[#09090b] flex flex-col md:flex-row overflow-hidden">
          {/* Sidebar Filters */}
          <div className="w-full md:w-80 bg-[#121214] border-b md:border-b-0 md:border-r border-zinc-800 p-6 flex flex-col gap-8 overflow-y-auto">
            <div className="flex justify-between items-center">
              <h2 className="text-xl font-bold text-white uppercase tracking-wider">{pickerCategory} Catalog</h2>
              <button onClick={() => setPickerCategory(null)} className="text-zinc-500 hover:text-white transition-colors bg-zinc-800/50 p-2 rounded-lg">✕</button>
            </div>
            
            <div className="space-y-6">
              <div>
                <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Search Catalog</label>
                <input 
                  type="text" 
                  placeholder="e.g. RTX 4070, Ryzen..." 
                  value={pickerSearch} 
                  onChange={e => setPickerSearch(e.target.value)} 
                  className="w-full bg-[#09090b] border border-zinc-700 text-white p-3 rounded-xl focus:outline-none focus:border-blue-500" 
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Max Price: RM {pickerMaxPrice}</label>
                <input 
                  type="range" 
                  min="50" 
                  max={getCategoryMaxPrice({ cpu: 'cpus', cooler: 'coolers', mobo: 'motherboards', ram: 'rams', gpu: 'gpus', psu: 'psus', case: 'cases' }[pickerCategory])} 
                  step="10" 
                  value={pickerMaxPrice} 
                  onChange={(e) => setPickerMaxPrice(Number(e.target.value))} 
                  className="range-slider w-full" 
                />
              </div>

              {/* Granular Compatibility Filters */}
              <div className="bg-blue-900/10 border border-blue-900/30 p-4 rounded-xl flex flex-col gap-3">
                <span className="block text-sm font-bold text-blue-400 mb-1">Enforce Compatibility</span>
                
                {['cpu', 'mobo'].includes(pickerCategory) && (
                  <label className="flex items-center gap-3 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={compatFilters.socket} 
                      onChange={e => setCompatFilters({...compatFilters, socket: e.target.checked})} 
                      className="accent-blue-500 w-4 h-4" 
                    />
                    <span className="text-sm text-zinc-300">Match CPU Socket</span>
                  </label>
                )}
                
                {['cpu', 'cooler'].includes(pickerCategory) && (
                  <label className="flex items-center gap-3 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={compatFilters.thermal} 
                      onChange={e => setCompatFilters({...compatFilters, thermal: e.target.checked})} 
                      className="accent-blue-500 w-4 h-4" 
                    />
                    <span className="text-sm text-zinc-300">Thermal Capacity Limits</span>
                  </label>
                )}

                {['mobo', 'ram'].includes(pickerCategory) && (
                  <label className="flex items-center gap-3 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={compatFilters.ram} 
                      onChange={e => setCompatFilters({...compatFilters, ram: e.target.checked})} 
                      className="accent-blue-500 w-4 h-4" 
                    />
                    <span className="text-sm text-zinc-300">RAM Generation (DDR4/5)</span>
                  </label>
                )}

                {['gpu', 'psu'].includes(pickerCategory) && (
                  <label className="flex items-center gap-3 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={compatFilters.power} 
                      onChange={e => setCompatFilters({...compatFilters, power: e.target.checked})} 
                      className="accent-blue-500 w-4 h-4" 
                    />
                    <span className="text-sm text-zinc-300">Power Supply Wattage</span>
                  </label>
                )}

                {['gpu', 'case'].includes(pickerCategory) && (
                  <label className="flex items-center gap-3 cursor-pointer">
                    <input 
                      type="checkbox" 
                      checked={compatFilters.spatial} 
                      onChange={e => setCompatFilters({...compatFilters, spatial: e.target.checked})} 
                      className="accent-blue-500 w-4 h-4" 
                    />
                    <span className="text-sm text-zinc-300">Case Spatial Clearance</span>
                  </label>
                )}
              </div>

            </div>
          </div>

          {/* Main Marketplace Grid */}
          <div className="flex-1 p-6 overflow-y-auto bg-[#09090b]">
            {pickerItems.length === 0 ? (
              <div className="text-center text-zinc-500 mt-20">No components match your filters.</div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                {pickerItems.map(item => (
                  <div key={item.id} className="bg-[#121214] border border-zinc-800 rounded-2xl p-5 hover:border-zinc-600 transition-colors flex flex-col justify-between">
                    <div>
                      <div className="text-xs text-zinc-500 font-bold mb-1 uppercase">{pickerCategory}</div>
                      <h3 className="text-white font-bold text-base leading-tight mb-3">{item.name}</h3>
                      
                      <div className="space-y-1 text-xs mb-6">
                        {item.socket && <div className="flex justify-between"><span className="text-zinc-500">Socket:</span><span className="text-zinc-300 font-medium">{item.socket}</span></div>}
                        {item.tdp && <div className="flex justify-between"><span className="text-zinc-500">TDP:</span><span className="text-zinc-300 font-medium">{item.tdp}W</span></div>}
                        {item.maxTdp && <div className="flex justify-between"><span className="text-zinc-500">Max Cooling:</span><span className="text-zinc-300 font-medium">{item.maxTdp}W</span></div>}
                        {item.ramType && <div className="flex justify-between"><span className="text-zinc-500">RAM Gen:</span><span className="text-zinc-300 font-medium">{item.ramType}</span></div>}
                        {item.type && <div className="flex justify-between"><span className="text-zinc-500">Type:</span><span className="text-zinc-300 font-medium">{item.type}</span></div>}
                        {item.lengthMm && <div className="flex justify-between"><span className="text-zinc-500">Length:</span><span className="text-zinc-300 font-medium">{item.lengthMm}mm</span></div>}
                        {item.wattage && <div className="flex justify-between"><span className="text-zinc-500">Wattage:</span><span className="text-zinc-300 font-medium">{item.wattage}W</span></div>}
                        {item.maxGpuLengthMm && <div className="flex justify-between"><span className="text-zinc-500">Max GPU:</span><span className="text-zinc-300 font-medium">{item.maxGpuLengthMm}mm</span></div>}
                      </div>
                    </div>
                    
                    <div className="flex items-center justify-between pt-4 border-t border-zinc-800">
                      <span className="text-emerald-400 font-black text-lg">RM {item.price}</span>
                      <button 
                        onClick={() => selectComponent(item)}
                        className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition-colors"
                      >
                        Select
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Authentication Modal */}
      {authModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-[#121214] border border-zinc-800 rounded-3xl p-8 w-full max-w-md shadow-2xl">
            <h2 className="text-2xl font-bold text-white mb-6">
              {authModal === 'login' ? 'Welcome Back' : 'Create Account'}
            </h2>
            <form onSubmit={handleAuth} className="space-y-4">
              {authModal === 'signup' && (
                <div>
                  <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Username</label>
                  <input type="text" required value={username} onChange={e => setUsername(e.target.value)} className="w-full bg-[#09090b] border border-zinc-700 text-white p-3 rounded-xl focus:outline-none focus:border-blue-500" />
                </div>
              )}
              <div>
                <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Email Address</label>
                <input type="email" required value={email} onChange={e => setEmail(e.target.value)} className="w-full bg-[#09090b] border border-zinc-700 text-white p-3 rounded-xl focus:outline-none focus:border-blue-500" />
              </div>
              <div>
                <label className="block text-xs font-bold text-zinc-500 uppercase tracking-wide mb-2">Password</label>
                <input type="password" required value={password} onChange={e => setPassword(e.target.value)} className="w-full bg-[#09090b] border border-zinc-700 text-white p-3 rounded-xl focus:outline-none focus:border-blue-500" />
              </div>
              <div className="pt-4 flex gap-3">
                <button type="button" onClick={closeModal} className="flex-1 py-3 bg-zinc-800 text-zinc-300 font-bold rounded-xl hover:bg-zinc-700">Cancel</button>
                <button type="submit" className="flex-1 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500">
                  {authModal === 'login' ? 'Login' : 'Sign Up'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}