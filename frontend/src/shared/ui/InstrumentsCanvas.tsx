import { useEffect, useMemo, useRef, useState } from "react";
import { Canvas, useFrame } from "@react-three/fiber";
import { Float, Sparkles } from "@react-three/drei";
import * as THREE from "three";

/**
 * Decorative, stylized (low-poly) music instruments rendered with plain
 * Three.js primitives — no external .glb/.gltf assets required.
 * Meant purely as ambient decoration behind the dashboard hero copy.
 */

type Props = {
    position: [number, number, number];
    scale?: number;
    spin?: boolean;
};

function usePrefersReducedMotion() {
    const [reduced, setReduced] = useState(false);

    useEffect(() => {
        const mq = window.matchMedia("(prefers-reduced-motion: reduce)");
        setReduced(mq.matches);
        const handler = (e: MediaQueryListEvent) => setReduced(e.matches);
        mq.addEventListener("change", handler);
        return () => mq.removeEventListener("change", handler);
    }, []);

    return reduced;
}

function GrandPiano({ position, scale = 1, spin = true }: Props) {
    const group = useRef<THREE.Group>(null);

    const caseGeometry = useMemo(() => {
        const shape = new THREE.Shape();
        shape.moveTo(-1.1, -0.9);
        shape.lineTo(0.9, -0.9);
        shape.quadraticCurveTo(1.15, -0.85, 1.15, -0.4);
        shape.quadraticCurveTo(1.15, 0.6, 0.15, 0.95);
        shape.quadraticCurveTo(-0.5, 1.15, -1.1, 0.75);
        shape.lineTo(-1.1, -0.9);
        return new THREE.ExtrudeGeometry(shape, {
            depth: 0.9,
            bevelEnabled: true,
            bevelThickness: 0.03,
            bevelSize: 0.03,
            bevelSegments: 2,
        });
    }, []);

    const keys = useMemo(() => {
        const arr: { black: boolean; x: number }[] = [];
        const count = 14;
        for (let i = 0; i < count; i++) {
            arr.push({ black: i % 2 === 0, x: -0.95 + i * 0.145 });
        }
        return arr;
    }, []);

    useFrame((_, delta) => {
        if (group.current && spin) group.current.rotation.y += delta * 0.25;
    });

    return (
        <group
            ref={group}
            position={position}
            scale={scale}
            rotation={[0, 0.4, 0]}
        >
            <mesh geometry={caseGeometry} rotation={[-Math.PI / 2, 0, 0]}>
                <meshStandardMaterial
                    color="#141522"
                    metalness={0.3}
                    roughness={0.35}
                />
            </mesh>

            <mesh position={[-0.2, 0.5, -0.3]} rotation={[-1, 0, 0.05]}>
                <boxGeometry args={[1.9, 0.04, 1.3]} />
                <meshStandardMaterial
                    color="#1c1e2c"
                    metalness={0.4}
                    roughness={0.25}
                />
            </mesh>

            <group position={[0.05, 0.02, 0.85]}>
                {keys.map((k, i) => (
                    <mesh key={i} position={[k.x, 0, 0]}>
                        <boxGeometry
                            args={[0.11, 0.05, k.black ? 0.22 : 0.32]}
                        />
                        <meshStandardMaterial
                            color={k.black ? "#0b0b10" : "#f5efe0"}
                        />
                    </mesh>
                ))}
            </group>

            {(
                [
                    [-0.85, -0.55, 0.5],
                    [0.75, -0.55, 0.5],
                    [0.0, -0.55, -0.65],
                ] as [number, number, number][]
            ).map((p, i) => (
                <mesh key={i} position={p}>
                    <cylinderGeometry args={[0.05, 0.06, 0.7, 12]} />
                    <meshStandardMaterial color="#141522" />
                </mesh>
            ))}
        </group>
    );
}

function Guitar({ position, scale = 1, spin = true }: Props) {
    const group = useRef<THREE.Group>(null);

    useFrame((_, delta) => {
        if (group.current && spin) group.current.rotation.y += delta * 0.4;
    });

    return (
        <group
            ref={group}
            position={position}
            scale={scale}
            rotation={[0.1, 0.6, 0.15]}
        >
            <mesh position={[0, -0.35, 0]} scale={[1, 0.85, 0.5]}>
                <sphereGeometry args={[0.55, 32, 32]} />
                <meshStandardMaterial
                    color="#a9682f"
                    roughness={0.4}
                    metalness={0.05}
                />
            </mesh>
            <mesh position={[0, 0.32, 0]} scale={[0.78, 0.7, 0.42]}>
                <sphereGeometry args={[0.5, 32, 32]} />
                <meshStandardMaterial
                    color="#a9682f"
                    roughness={0.4}
                    metalness={0.05}
                />
            </mesh>

            <mesh position={[0, -0.15, 0.24]} rotation={[Math.PI / 2, 0, 0]}>
                <cylinderGeometry args={[0.14, 0.14, 0.02, 24]} />
                <meshStandardMaterial color="#1a120a" />
            </mesh>

            <mesh position={[0, 1.05, 0.02]}>
                <boxGeometry args={[0.16, 1.15, 0.1]} />
                <meshStandardMaterial color="#3a2416" roughness={0.6} />
            </mesh>
            <mesh position={[0, 1.68, 0.02]}>
                <boxGeometry args={[0.26, 0.28, 0.08]} />
                <meshStandardMaterial color="#241509" roughness={0.6} />
            </mesh>

            {[-0.05, -0.017, 0.017, 0.05].map((x, i) => (
                <mesh key={i} position={[x, 0.7, 0.07]}>
                    <cylinderGeometry args={[0.004, 0.004, 1.9, 6]} />
                    <meshStandardMaterial
                        color="#d9d2c4"
                        metalness={0.8}
                        roughness={0.2}
                    />
                </mesh>
            ))}

            {[-0.1, 0.1].map((x, i) => (
                <mesh key={i} position={[x, 1.78, 0.05]}>
                    <cylinderGeometry args={[0.02, 0.02, 0.12, 8]} />
                    <meshStandardMaterial
                        color="#d4af37"
                        metalness={0.8}
                        roughness={0.3}
                    />
                </mesh>
            ))}
        </group>
    );
}

function Violin({ position, scale = 1, spin = true }: Props) {
    const group = useRef<THREE.Group>(null);

    useFrame((_, delta) => {
        if (group.current && spin) group.current.rotation.y -= delta * 0.5;
    });

    return (
        <group
            ref={group}
            position={position}
            scale={scale}
            rotation={[0.15, -0.5, -0.1]}
        >
            <mesh position={[0, -0.22, 0]} scale={[1, 0.8, 0.42]}>
                <sphereGeometry args={[0.34, 32, 32]} />
                <meshStandardMaterial
                    color="#7a3418"
                    roughness={0.35}
                    metalness={0.05}
                />
            </mesh>
            <mesh position={[0, 0.2, 0]} scale={[0.72, 0.68, 0.36]}>
                <sphereGeometry args={[0.3, 32, 32]} />
                <meshStandardMaterial
                    color="#7a3418"
                    roughness={0.35}
                    metalness={0.05}
                />
            </mesh>

            {[-0.14, 0.14].map((x, i) => (
                <mesh
                    key={i}
                    position={[x, -0.05, 0.16]}
                    rotation={[0, 0, i === 0 ? 0.35 : -0.35]}
                >
                    <boxGeometry args={[0.02, 0.18, 0.005]} />
                    <meshStandardMaterial color="#150a05" />
                </mesh>
            ))}

            <mesh position={[0, 0.62, 0.01]}>
                <boxGeometry args={[0.09, 0.62, 0.06]} />
                <meshStandardMaterial color="#241209" roughness={0.6} />
            </mesh>
            <mesh position={[0, 0.98, 0.02]}>
                <torusGeometry args={[0.06, 0.025, 12, 24]} />
                <meshStandardMaterial color="#241209" roughness={0.6} />
            </mesh>

            {[-0.02, -0.007, 0.007, 0.02].map((x, i) => (
                <mesh key={i} position={[x, 0.35, 0.045]}>
                    <cylinderGeometry args={[0.0025, 0.0025, 1.0, 6]} />
                    <meshStandardMaterial
                        color="#e7e2d6"
                        metalness={0.7}
                        roughness={0.2}
                    />
                </mesh>
            ))}

            <mesh position={[0.1, -0.42, 0.05]}>
                <boxGeometry args={[0.12, 0.05, 0.1]} />
                <meshStandardMaterial
                    color="#d4af37"
                    metalness={0.6}
                    roughness={0.3}
                />
            </mesh>
        </group>
    );
}

function MusicalNote({
    position,
    scale = 1,
    spin = true,
    color = "#d4af37",
}: Props & { color?: string }) {
    const group = useRef<THREE.Group>(null);

    useFrame((state) => {
        if (group.current && spin)
            group.current.rotation.y = state.clock.elapsedTime * 0.8;
    });

    return (
        <group ref={group} position={position} scale={scale}>
            <mesh rotation={[Math.PI / 2.4, 0, 0]} position={[0, -0.3, 0]}>
                <sphereGeometry args={[0.14, 20, 20]} />
                <meshStandardMaterial
                    color={color}
                    metalness={0.5}
                    roughness={0.3}
                />
            </mesh>
            <mesh position={[0.13, 0.15, 0]}>
                <cylinderGeometry args={[0.018, 0.018, 0.9, 8]} />
                <meshStandardMaterial
                    color={color}
                    metalness={0.5}
                    roughness={0.3}
                />
            </mesh>
            <mesh position={[0.2, 0.5, 0]} rotation={[0, 0, -0.6]}>
                <boxGeometry args={[0.16, 0.09, 0.02]} />
                <meshStandardMaterial
                    color={color}
                    metalness={0.5}
                    roughness={0.3}
                />
            </mesh>
        </group>
    );
}

function Scene({ reduceMotion }: { reduceMotion: boolean }) {
    const speedMul = reduceMotion ? 0.15 : 1;
    const spin = !reduceMotion;

    return (
        <>
            <ambientLight intensity={0.55} color="#fff6e5" />
            <directionalLight
                position={[4, 5, 5]}
                intensity={1.1}
                color="#fff2d6"
            />
            <pointLight
                position={[-4, 1.5, -2]}
                intensity={0.9}
                color="#d4af37"
            />
            <pointLight position={[3, -1, 3]} intensity={0.4} color="#8ea2ff" />

            <Float
                speed={1.6 * speedMul}
                rotationIntensity={0.6 * speedMul}
                floatIntensity={1.3 * speedMul}
            >
                <GrandPiano
                    position={[-2.5, -0.4, 0]}
                    scale={0.85}
                    spin={spin}
                />
            </Float>
            <Float
                speed={2.1 * speedMul}
                rotationIntensity={0.8 * speedMul}
                floatIntensity={1.6 * speedMul}
            >
                <Guitar position={[2.3, 0.15, -0.6]} scale={0.85} spin={spin} />
            </Float>
            <Float
                speed={2.6 * speedMul}
                rotationIntensity={1 * speedMul}
                floatIntensity={1.8 * speedMul}
            >
                <Violin position={[0.3, 1.05, -1.2]} scale={0.95} spin={spin} />
            </Float>
            <Float
                speed={3 * speedMul}
                rotationIntensity={1.2 * speedMul}
                floatIntensity={2 * speedMul}
            >
                <MusicalNote
                    position={[-0.9, 1.2, 0.4]}
                    scale={0.6}
                    spin={spin}
                />
            </Float>
            <Float
                speed={2.4 * speedMul}
                rotationIntensity={1 * speedMul}
                floatIntensity={1.7 * speedMul}
            >
                <MusicalNote
                    position={[3.3, 1.3, 0.2]}
                    scale={0.45}
                    spin={spin}
                    color="#f5efe0"
                />
            </Float>

            {!reduceMotion && (
                <Sparkles
                    count={40}
                    scale={[9, 3.5, 4]}
                    size={2}
                    speed={0.3}
                    opacity={0.5}
                    color="#d4af37"
                />
            )}
        </>
    );
}

export function InstrumentsCanvas({ className }: { className?: string }) {
    const reduceMotion = usePrefersReducedMotion();

    return (
        <div className={className ?? "h-full w-full"}>
            <Canvas
                dpr={[1, 1.75]}
                camera={{ position: [0, 0.6, 7.2], fov: 42 }}
                gl={{ antialias: true, alpha: true }}
            >
                <Scene reduceMotion={reduceMotion} />
            </Canvas>
        </div>
    );
}
